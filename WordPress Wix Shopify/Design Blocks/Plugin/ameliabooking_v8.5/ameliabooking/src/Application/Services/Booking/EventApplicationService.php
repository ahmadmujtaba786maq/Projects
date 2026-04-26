<?php

namespace AmeliaBooking\Application\Services\Booking;

use AmeliaBooking\Application\Services\Gallery\GalleryApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventTicket;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTag;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventPeriodFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventTicketFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\RecurringFactory;
use AmeliaBooking\Domain\Services\Booking\EventDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventPeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventTicketRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventPeriodsRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventProvidersRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventTagsRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventTicketRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponEventRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldEventRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationsToEntitiesRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\Tax\TaxEntityRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class EventApplicationService
 *
 * @package AmeliaBooking\Application\Services\Booking
 */
class EventApplicationService
{
    private $container;

    /**
     * EventApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $data
     *
     * @return Event
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws Exception
     */
    public function build($data)
    {
        foreach ($data['periods'] as &$period) {
            if (!empty($data['utc'])) {
                $period['periodStart'] = DateTimeService::getCustomDateTimeFromUtc(
                    $period['periodStart']
                );

                $period['periodEnd'] = DateTimeService::getCustomDateTimeFromUtc(
                    $period['periodEnd']
                );
            } elseif (!empty($data['timeZone'])) {
                $period['periodStart'] = DateTimeService::getDateTimeObjectInTimeZone(
                    $period['periodStart'],
                    $data['timeZone']
                )->setTimezone(DateTimeService::getTimeZone())->format('Y-m-d H:i:s');

                $period['periodEnd'] = DateTimeService::getDateTimeObjectInTimeZone(
                    $period['periodEnd'],
                    $data['timeZone']
                )->setTimezone(DateTimeService::getTimeZone())->format('Y-m-d H:i:s');
            }
        }

        return EventFactory::create($data);
    }

    /**
     * @param Event $event
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function add($event)
    {
        /** @var EventDomainService $eventDomainService */
        $eventDomainService = $this->container->get('domain.booking.event.service');

        $bookingOpensSame  = $event->getBookingOpensRec() === 'same';
        $bookingClosesSame = $event->getBookingClosesRec() === 'same';
        $ticketRangeSame   = $event->getTicketRangeRec() === 'same';

        $events = new Collection();

        if ($event->getRecurring()) {
            $event->getRecurring()->setOrder(new WholeNumber(1));
        }

        $this->addSingle($event);
        $events->addItem($event);
        $event->setParentId(new Id($event->getId()->getValue()));

        $eventStarts = $event->getPeriods()->getItem(0)->getPeriodStart()->getValue();
        $eventEnds   = null;
        if (!$bookingOpensSame) {
            if ($event->getBookingOpens()) {
                $eventDateDiff = $event->getBookingOpens()->getValue()->diff($eventStarts);
            } else {
                $lastIndex = $event->getPeriods()->length() - 1;
                $eventEnds = $event->getPeriods()->getItem($lastIndex)->getPeriodEnd()->getValue();
            }
        }

        if (!$bookingClosesSame && $event->getBookingCloses()) {
            $eventDateDiffCloses = $event->getBookingCloses()->getValue()->diff($eventStarts);
        }

        if (!$ticketRangeSame) {
            $allTicketDiff = [];

            /** @var EventTicket $ticket */
            foreach ($event->getCustomTickets()->getItems() as $ticketIndex => $ticket) {
                $allTicketDiff[$ticketIndex] = [];

                $ticketDates = json_decode($ticket->getDateRanges()->getValue(), true);

                foreach ($ticketDates as $dateIndex => $ticketDate) {
                    $ticketDateStart = DateTimeService::getCustomDateTimeObject($ticketDate['startDate']);

                    $ticketDateEnd = DateTimeService::getCustomDateTimeObject($ticketDate['endDate']);

                    $allTicketDiff[$ticketIndex][$dateIndex] = [
                        'startDiff' => $ticketDateStart->diff($eventStarts),
                        'endDiff'   => $ticketDateEnd->diff($eventStarts),
                    ];
                }
            }
        }


        if ($event->getRecurring()) {
            $recurringEventsPeriods = $eventDomainService->getRecurringEventsPeriods(
                $event->getRecurring(),
                $event->getPeriods()
            );

            /** @var Collection $recurringEventPeriods */
            foreach ($recurringEventsPeriods as $key => $recurringEventPeriods) {
                $order = $recurringEventPeriods['order'];

                $event = EventFactory::create($event->toArray());

                $event->getRecurring()->setOrder(new WholeNumber($order));

                $event->setPeriods($recurringEventPeriods['periods']);

                $periodStart = $event->getPeriods()->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s');
                if (!$bookingOpensSame) {
                    if ($eventDateDiff) {
                        $periodStartOpen = DateTimeService::getCustomDateTimeObject($periodStart)->sub($eventDateDiff);
                        $event->setBookingOpens(new DateTimeValue($periodStartOpen));
                    } else {
                        $event->setBookingOpens(new DateTimeValue($eventEnds));
                    }
                    $lastIndex = $event->getPeriods()->length() - 1;
                    $eventEnds = $event->getPeriods()->getItem($lastIndex)->getPeriodEnd()->getValue();
                }

                if (!$bookingClosesSame) {
                    $periodStartClose = DateTimeService::getCustomDateTimeObject($periodStart);
                    if (isset($eventDateDiffCloses)) {
                        $periodStartClose = $periodStartClose->sub($eventDateDiffCloses);
                    }
                    $event->setBookingCloses(new DateTimeValue($periodStartClose));
                }

                if (!$ticketRangeSame) {
                    /** @var EventTicket $ticket */
                    foreach ($event->getCustomTickets()->getItems() as $ticketIndex => $ticket) {
                        $ticketDates = json_decode($ticket->getDateRanges()->getValue(), true);

                        foreach ($ticketDates as $dateIndex => &$ticketDate) {
                            $ticketDate = array_merge(
                                $ticketDate,
                                [
                                    'startDate' => DateTimeService::getCustomDateTimeObject(
                                        $periodStart
                                    )->sub($allTicketDiff[$ticketIndex][$dateIndex]['startDiff'])->format('Y-m-d'),
                                    'endDate'   => DateTimeService::getCustomDateTimeObject(
                                        $periodStart
                                    )->sub($allTicketDiff[$ticketIndex][$dateIndex]['endDiff'])->format('Y-m-d'),
                                ]
                            );
                        }

                        $ticket->setDateRanges(new Json(json_encode($ticketDates)));
                    }
                }

                $this->addSingle($event);
                $events->addItem($event);
            }
        }

        return $events;
    }

    /**
     * @param Event $oldEvent
     * @param Event $newEvent
     * @param bool  $updateFollowing
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws ContainerException
     */
    public function update($oldEvent, $newEvent, $updateFollowing)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var EventDomainService $eventDomainService */
        $eventDomainService = $this->container->get('domain.booking.event.service');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        /** @var Collection $rescheduledEvents */
        $rescheduledEvents = new Collection();

        /** @var Collection $clonedEvents */
        $clonedEvents = new Collection();

        /** @var Collection $addedEvents */
        $addedEvents = new Collection();

        /** @var Collection $deletedEvents */
        $deletedEvents = new Collection();

        $clonedEvents->addItem(EventFactory::create($oldEvent->toArray()), $oldEvent->getId()->getValue());

        $isNewRecurring = $this->isSeparateRecurringEvent($newEvent, $oldEvent);

        $isRescheduled = $newEvent->getPeriods()->toArray() !== $oldEvent->getPeriods()->toArray();

        $bookingOpensSame  = $newEvent->getBookingOpensRec() === 'same';
        $bookingClosesSame = $newEvent->getBookingClosesRec() === 'same';
        $ticketRangeSame   = $newEvent->getTicketRangeRec() === 'same';

        if ($isNewRecurring) {
            $newEvent->getRecurring()->setOrder(new WholeNumber(1));
        }

        if ($isRescheduled) {
            $newEvent->setInitialEventStart($oldEvent->getPeriods()->getItem(0)->getPeriodStart());
            $newEvent->setInitialEventEnd($oldEvent->getPeriods()->getItem($oldEvent->getPeriods()->length() - 1)->getPeriodEnd());
            $rescheduledEvents->addItem($newEvent, $newEvent->getId()->getValue());
        }

        $this->updateSingle($oldEvent, $newEvent, false);

        if (!$newEvent->getRecurring()) {
            $eventRepository->updateParentId($newEvent->getId()->getValue(), null);
        }

        $followingEvents = null;
        // update following events parentId, if new event recurring value is removed and if it's origin event
        if (!$newEvent->getRecurring() && $oldEvent->getRecurring() && !$newEvent->getParentId()) {
            /** @var Collection $followingEvents */
            $followingEvents = $eventAS->getEventsByCriteria(
                [
                    'parentId' => $newEvent->getId()->getValue(),
                ],
                [
                    'fetchEventsPeriods'   => true,
                    'fetchEventsTickets'   => true,
                    'fetchEventsTags'      => true,
                    'fetchEventsProviders' => true,
                    'fetchEventsImages'    => true,
                    'fetchBookings'        => true,
                    'fetchBookingsUsers'   => true,
                    'ordered'              => true,
                ],
                0
            );

            $firstFollowingEventId = null;

            /** @var Event $followingEvent */
            foreach ($followingEvents->getItems() as $key => $followingEvent) {
                if (!$clonedEvents->keyExists($followingEvent->getId()->getValue())) {
                    $clonedEvents->addItem(
                        EventFactory::create($followingEvent->toArray()),
                        $followingEvent->getId()->getValue()
                    );
                }

                if ($followingEvent->getId()->getValue() > $newEvent->getId()->getValue()) {
                    $eventRepository->updateParentId($followingEvent->getId()->getValue(), $firstFollowingEventId);

                    if ($firstFollowingEventId === null) {
                        $firstFollowingEventId = $followingEvent->getId()->getValue();
                    }
                }
            }
        }

        if ($updateFollowing && $newEvent->getRecurring()) {
            /** @var Collection $followingEvents */
            $followingEvents = $eventAS->getEventsByCriteria(
                [
                    'parentId' => $newEvent->getParentId() ?
                        $newEvent->getParentId()->getValue() : $newEvent->getId()->getValue(),
                ],
                [
                    'fetchEventsPeriods'   => true,
                    'fetchEventsTickets'   => true,
                    'fetchEventsTags'      => true,
                    'fetchEventsProviders' => true,
                    'fetchEventsImages'    => true,
                    'fetchBookings'        => true,
                    'fetchBookingsUsers'   => true,
                    'ordered'              => true,
                ],
                0
            );

            /** @var Event $firstEvent **/
            $firstEvent = $followingEvents->getItem($followingEvents->keys()[0]);

            /** @var Collection $clonedOriginEventPeriods **/
            $clonedOriginEventPeriods = $eventDomainService->getClonedEventPeriods(
                $isNewRecurring ? $newEvent->getPeriods() : $firstEvent->getPeriods(),
                false
            );

            $followingRecurringOrder = $newEvent->getRecurring()->getOrder()->getValue();

            $eventEnds   = null;
            $eventStarts = $newEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue();

            if (!$bookingOpensSame) {
                if ($newEvent->getBookingOpens()) {
                    $eventDateDiff = $newEvent->getBookingOpens()->getValue()->diff($eventStarts);
                } else {
                    $lastIndex = $newEvent->getPeriods()->length() - 1;
                    $eventEnds = $newEvent->getPeriods()->getItem($lastIndex)->getPeriodEnd()->getValue();
                }
            }

            if (!$bookingClosesSame) {
                if ($newEvent->getBookingCloses()) {
                    $eventDateDiffCloses = $newEvent->getBookingCloses()->getValue()->diff($eventStarts);
                }
            }

            if (!$ticketRangeSame) {
                $allTicketDiff = [];

                $index = 0;

                /** @var EventTicket $ticket */
                foreach ($newEvent->getCustomTickets()->getItems() as $ticketIndex => $ticket) {
                    $allTicketDiff[$ticketIndex] = [];

                    $ticketDates = json_decode($ticket->getDateRanges()->getValue(), true);

                    foreach ($ticketDates as $dateIndex => $ticketDate) {
                        $ticketDateStart = DateTimeService::getCustomDateTimeObject($ticketDate['startDate']);

                        $ticketDateEnd = DateTimeService::getCustomDateTimeObject($ticketDate['endDate']);

                        $allTicketDiff[$index][$dateIndex] = [
                            'startDiff' => $ticketDateStart->diff($eventStarts),
                            'endDiff'   => $ticketDateEnd->diff($eventStarts),
                        ];
                    }

                    $index++;
                }
            }

            /** @var EventPeriod|null $lastPeriod */
            $lastPeriod = null;

            /** @var Event $followingEvent */
            foreach ($followingEvents->getItems() as $key => $followingEvent) {
                if ($newEvent->getId()->getValue() < $followingEvent->getId()->getValue()) {
                    $lastPeriod = $this->getLastPeriod($followingEvent);
                }
            }

            /** @var Event $followingEvent */
            foreach ($followingEvents->getItems() as $key => $followingEvent) {
                if (!$clonedEvents->keyExists($followingEvent->getId()->getValue())) {
                    $clonedEvents->addItem(
                        EventFactory::create($followingEvent->toArray()),
                        $followingEvent->getId()->getValue()
                    );
                }

                if ($followingEvent->getId()->getValue() < $newEvent->getId()->getValue()) {
                    $followingEvent->getRecurring()->setUntil(
                        $isNewRecurring && $lastPeriod
                            ? $lastPeriod->getPeriodStart()
                            : $newEvent->getRecurring()->getUntil()
                    );

                    $this->updateSingle($followingEvent, $followingEvent, true);
                }

                if ($isNewRecurring && $followingEvent->getId()->getValue() === $newEvent->getId()->getValue()) {
                    $eventRepository->updateParentId($newEvent->getId()->getValue(), null);
                }

                if ($followingEvent->getId()->getValue() > $newEvent->getId()->getValue()) {
                    $followingEvent->setRecurring(
                        RecurringFactory::create(
                            [
                                'cycle' => $newEvent->getRecurring()->getCycle()->getValue(),
                                'cycleInterval' => $newEvent->getRecurring()->getCycleInterval()->getValue(),
                                'monthlyRepeat' => $newEvent->getRecurring()->getMonthlyRepeat(),
                                'monthlyOnRepeat' => $newEvent->getRecurring()->getMonthlyOnRepeat(),
                                'monthlyOnDay'  => $newEvent->getRecurring()->getMonthlyOnDay(),
                                'monthDate'  => $newEvent->getRecurring()->getMonthDate()
                                    ? $newEvent->getRecurring()->getMonthDate()->getValue()->format('Y-m-d H:i:s')
                                    : null,
                                'until' => $newEvent->getRecurring()->getUntil()->getValue()->format('Y-m-d H:i:s'),
                                'order' =>
                                    !$isNewRecurring &&
                                    $followingEvent->getRecurring() &&
                                    $followingEvent->getRecurring()->getOrder() &&
                                    $followingEvent->getRecurring()->getOrder()->getValue()
                                        ? $followingEvent->getRecurring()->getOrder()->getValue()
                                        : ++$followingRecurringOrder
                            ]
                        )
                    );

                    /** @var Collection $clonedFollowingEventPeriods */
                    $clonedFollowingEventPeriods = $eventDomainService->getClonedEventPeriods(
                        $followingEvent->getPeriods(),
                        true
                    );

                    $clonedFollowingEventTickets = $eventDomainService->getClonedEventTickets($followingEvent->getCustomTickets());

                    $eventDomainService->buildFollowingEvent($followingEvent, $newEvent, $clonedOriginEventPeriods);

                    if ($isRescheduled && $followingEvent->getStatus()->getValue() === BookingStatus::APPROVED) {
                        $followingEvent->setInitialEventStart($clonedFollowingEventPeriods->getItem(0)->getPeriodStart());
                        $followingEvent->setInitialEventEnd($clonedFollowingEventPeriods->getItem($clonedFollowingEventPeriods->length() - 1)->getPeriodEnd());

                        $rescheduledEvents->addItem($followingEvent, $followingEvent->getId()->getValue());
                    }

                    if ($isNewRecurring) {
                        $followingEvent->setParentId($newEvent->getId());
                    }

                    /** @var Event $followingEventClone */
                    $followingEventClone = EventFactory::create($followingEvent->toArray());

                    $followingEventClone->setPeriods($clonedFollowingEventPeriods);

                    $followingEventClone->setCustomTickets($clonedFollowingEventTickets);

                    $periodStart = $followingEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s');

                    if (!$bookingOpensSame) {
                        if ($eventDateDiff) {
                            $periodStartOpen = DateTimeService::getCustomDateTimeObject($periodStart)->sub($eventDateDiff);
                            $followingEvent->setBookingOpens(new DateTimeValue($periodStartOpen));
                        } else {
                            $followingEvent->setBookingOpens($eventEnds ? new DateTimeValue($eventEnds) : null);
                            if ($eventEnds) {
                                $lastIndex = $followingEvent->getPeriods()->length() - 1;
                                $eventEnds = $followingEvent->getPeriods()->getItem($lastIndex)->getPeriodEnd()->getValue();
                            }
                        }
                    }

                    if (!$bookingClosesSame) {
                        $periodStartClose = DateTimeService::getCustomDateTimeObject($periodStart);
                        if ($eventDateDiffCloses) {
                            $periodStartClose = $periodStartClose->sub($eventDateDiffCloses);
                        }
                        $followingEvent->setBookingCloses(new DateTimeValue($periodStartClose));
                    }

                    if (!$ticketRangeSame) {
                        $index = 0;

                        /** @var EventTicket $ticket */
                        foreach ($followingEvent->getCustomTickets()->getItems() as $ticketIndex => $ticket) {
                            $ticketDates = json_decode($ticket->getDateRanges()->getValue(), true);

                            foreach ($ticketDates as $dateIndex => &$ticketDate) {
                                $ticketDate = array_merge(
                                    $ticketDate,
                                    [
                                        'startDate' => DateTimeService::getCustomDateTimeObject(
                                            $periodStart
                                        )->sub($allTicketDiff[$index][$dateIndex]['startDiff'])->format('Y-m-d'),
                                        'endDate'   => DateTimeService::getCustomDateTimeObject(
                                            $periodStart
                                        )->sub($allTicketDiff[$index][$dateIndex]['endDiff'])->format('Y-m-d'),
                                    ]
                                );
                            }

                            $index++;

                            $ticket->setDateRanges(new Json(json_encode($ticketDates)));
                        }
                    }

                    $this->updateSingle($followingEventClone, $followingEvent, false);
                }
            }

            /** @var Event $lastEvent **/
            $lastEvent = $followingEvents->getItem($followingEvents->keys()[sizeof($followingEvents->keys()) - 1]);

            $lastRecurringOrder = $lastEvent->getRecurring()->getOrder()->getValue();


            $eventEnds   = null;
            $eventStarts = $newEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue();
            if (!$bookingOpensSame) {
                if ($newEvent->getBookingOpens()) {
                    $eventDateDiff = $newEvent->getBookingOpens()->getValue()->diff($eventStarts);
                } else {
                    $lastIndex = $lastEvent->getPeriods()->length() - 1;
                    $eventEnds = $lastEvent->getPeriods()->getItem($lastIndex)->getPeriodEnd()->getValue();
                }
            }
            if (!$bookingClosesSame) {
                if ($newEvent->getBookingCloses()) {
                    $eventDateDiffCloses = $newEvent->getBookingCloses()->getValue()->diff($eventStarts);
                }
            }

            while (
                $lastEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue() <=
                $newEvent->getRecurring()->getUntil()->getValue()
            ) {
                /** @var Event $lastEvent **/
                $lastEvent = EventFactory::create(
                    [
                        'name'  => $newEvent->getName()->getValue(),
                        'price' => $newEvent->getPrice()->getValue(),
                    ]
                );

                $lastEvent->setRecurring(
                    RecurringFactory::create(
                        [
                            'cycle' => $newEvent->getRecurring()->getCycle()->getValue(),
                            'cycleInterval' => $newEvent->getRecurring()->getCycleInterval()->getValue(),
                            'monthlyRepeat' => $newEvent->getRecurring()->getMonthlyRepeat(),
                            'monthlyOnRepeat' => $newEvent->getRecurring()->getMonthlyOnRepeat(),
                            'monthlyOnDay'  => $newEvent->getRecurring()->getMonthlyOnDay(),
                            'monthDate'  =>
                                $newEvent->getRecurring()->getMonthDate() ?
                                    $newEvent->getRecurring()->getMonthDate()->getValue()->format('Y-m-d H:i:s') :
                                    null,
                            'until' => $newEvent->getRecurring()->getUntil()->getValue()->format('Y-m-d H:i:s'),
                            'order' => ++$lastRecurringOrder
                        ]
                    )
                );

                $lastEvent->setPeriods($eventDomainService->getClonedEventPeriods($clonedOriginEventPeriods, false));

                $success = $eventDomainService->buildFollowingEvent(
                    $lastEvent,
                    $newEvent,
                    $eventDomainService->getClonedEventPeriods($clonedOriginEventPeriods, false)
                );

                if (!$success) {
                    ++$lastRecurringOrder;
                    continue;
                }

                $lastEvent->setParentId(
                    !$isNewRecurring && $newEvent->getParentId() ?
                        $newEvent->getParentId() : $newEvent->getId()
                );

                $lastEventTickets = new Collection();

                /** @var EventTicket $newEventTicket **/
                foreach ($newEvent->getCustomTickets()->getItems() as $newEventTicket) {
                    $newEventTicketData = $newEventTicket->toArray();

                    unset($newEventTicketData['id']);

                    unset($newEventTicketData['sold']);

                    $lastEventTickets->addItem(
                        EventTicketFactory::create(
                            $newEventTicketData
                        )
                    );
                }

                $lastEvent->setCustomTickets($lastEventTickets);

                if (
                    $lastEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue() <=
                    $newEvent->getRecurring()->getUntil()->getValue()
                ) {
                    /** @var EventPeriod $eventPeriod **/
                    foreach ($lastEvent->getPeriods()->getItems() as $key => $eventPeriod) {
                        /** @var EventPeriod $newEventPeriod **/
                        $newEventPeriod = EventPeriodFactory::create(
                            array_merge(
                                $eventPeriod->toArray(),
                                ['zoomMeeting' => null]
                            )
                        );

                        $lastEvent->getPeriods()->placeItem($newEventPeriod, $key, true);
                    }

                    $periodStart = $lastEvent->getPeriods()->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s');
                    if (!$bookingOpensSame) {
                        if ($eventDateDiff) {
                            $periodStartOpen = DateTimeService::getCustomDateTimeObject($periodStart)->sub($eventDateDiff);
                            $lastEvent->setBookingOpens(new DateTimeValue($periodStartOpen));
                        } else {
                            $lastEvent->setBookingOpens($eventEnds ? new DateTimeValue($eventEnds) : null);
                            if ($eventEnds) {
                                $lastIndex = $lastEvent->getPeriods()->length() - 1;
                                $eventEnds = $lastEvent->getPeriods()->getItem($lastIndex)->getPeriodEnd()->getValue();
                            }
                        }
                    }
                    if (!$bookingOpensSame) {
                        $periodStartClose = DateTimeService::getCustomDateTimeObject($periodStart);
                        if (isset($eventDateDiffCloses)) {
                            $periodStartClose = $periodStartClose->sub($eventDateDiffCloses);
                        }
                        $lastEvent->setBookingCloses(new DateTimeValue($periodStartClose));
                    }

                    if (!$ticketRangeSame) {
                        $index = 0;

                        /** @var EventTicket $ticket */
                        foreach ($lastEvent->getCustomTickets()->getItems() as $ticketIndex => $ticket) {
                            $ticketDates = json_decode($ticket->getDateRanges()->getValue(), true);

                            foreach ($ticketDates as $dateIndex => &$ticketDate) {
                                $ticketDate = array_merge(
                                    $ticketDate,
                                    [
                                        'startDate' => DateTimeService::getCustomDateTimeObject(
                                            $periodStart
                                        )->sub($allTicketDiff[$index][$dateIndex]['startDiff'])->format('Y-m-d'),
                                        'endDate'   => DateTimeService::getCustomDateTimeObject(
                                            $periodStart
                                        )->sub($allTicketDiff[$index][$dateIndex]['endDiff'])->format('Y-m-d'),
                                    ]
                                );
                            }

                            $index++;

                            $ticket->setDateRanges(new Json(json_encode($ticketDates)));
                        }
                    }

                    $this->addSingle($lastEvent);

                    $addedEvents->addItem($lastEvent, $lastEvent->getId()->getValue());
                }
            }

            if ($isNewRecurring) {
                /** @var EventPeriod $lastPeriod */
                $lastPeriod = $this->getLastPeriod($lastEvent);

                if ($lastPeriod->getPeriodStart()->getValue() > $newEvent->getRecurring()->getUntil()->getValue()) {
                    $eventRepository->updateFieldByColumn(
                        'recurringUntil',
                        $lastPeriod->getPeriodStart()->getValue()->format('Y-m-d') . ' 00:00:00',
                        'parentId',
                        $newEvent->getId()->getValue()
                    );

                    $eventRepository->updateFieldById(
                        $newEvent->getId()->getValue(),
                        $lastPeriod->getPeriodStart()->getValue()->format('Y-m-d') . ' 00:00:00',
                        'recurringUntil'
                    );
                }
            }
        }

        $clonedEditedEvents = $this->getEditedEvents($clonedEvents, $newEvent, $updateFollowing, $followingEvents);

        if ($newEvent->getZoomUserId() && !$oldEvent->getZoomUserId()) {
            /** @var Event $event **/
            foreach ($clonedEvents->getItems() as $event) {
                $event->setZoomUserId($newEvent->getZoomUserId());
            }
        }

        if (
            $newEvent->getDescription() &&
            (
                ($newEvent->getDescription() ? $newEvent->getDescription()->getValue() : null) !==
                ($oldEvent->getDescription() ? $oldEvent->getDescription()->getValue() : null) ||
                $newEvent->getName()->getValue() !== $oldEvent->getName()->getValue()
            )
        ) {
            /** @var Event $event **/
            foreach ($clonedEvents->getItems() as $event) {
                $event->setDescription($newEvent->getDescription());
            }
        }

        return [
            'rescheduled' => $rescheduledEvents->toArray(),
            'added'       => $addedEvents->toArray(),
            'deleted'     => $deletedEvents->toArray(),
            'cloned'      => $clonedEvents->toArray(),
            'edited'      => $clonedEditedEvents
        ];
    }

    /**
     * @param Event $lastEvent
     *
     * @return EventPeriod
     */
    public function getLastPeriod($lastEvent)
    {
        /** @var EventPeriod|null $latestPeriod **/
        $latestPeriod = null;

        /** @var EventPeriod $period **/
        foreach ($lastEvent->getPeriods()->getItems() as $period) {
            if (
                $latestPeriod === null ||
                $period->getPeriodStart()->getValue() > $latestPeriod->getPeriodStart()->getValue()
            ) {
                $latestPeriod = $period;
            }
        }

        return $latestPeriod;
    }

    /**
     * @param Event  $event
     * @param String $status
     * @param bool   $updateFollowing
     *
     * @return Collection
     *
     * @throws \Slim\Exception\ContainerException
     * @throws ContainerValueNotFoundException
     * @throws \InvalidArgumentException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function updateStatus($event, $status, $updateFollowing)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        /** @var Collection $updatedEvents */
        $updatedEvents = new Collection();

        if ($event->getStatus()->getValue() !== $status) {
            $eventRepository->updateStatusById($event->getId()->getValue(), $status);

            $event->setStatus(new BookingStatus($status));

            $updatedEvents->addItem($event, $event->getId()->getValue());

            /** @var CustomerBooking $booking */
            foreach ($event->getBookings()->getItems() as $booking) {
                if (
                    $status === BookingStatus::REJECTED &&
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED
                ) {
                    $booking->setChangedStatus(new BooleanValueObject(true));
                }

                if (
                    $status === BookingStatus::APPROVED &&
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED
                ) {
                    $booking->setChangedStatus(new BooleanValueObject(true));
                }
            }
        }

        if ($updateFollowing) {
            /** @var Collection $followingEvents */
            $followingEvents = $eventAS->getEventsByCriteria(
                [
                    'parentId' => $event->getParentId() ?
                        $event->getParentId()->getValue() : $event->getId()->getValue(),
                ],
                [
                    'fetchEventsPeriods'   => true,
                    'fetchEventsTickets'   => true,
                    'fetchEventsTags'      => true,
                    'fetchEventsProviders' => true,
                    'fetchEventsImages'    => true,
                    'fetchBookings'        => true,
                    'fetchBookingsUsers'   => true,
                ],
                0
            );

            /** @var Event $followingEvent */
            foreach ($followingEvents->getItems() as $key => $followingEvent) {
                if ($followingEvent->getId()->getValue() > $event->getId()->getValue()) {
                    $followingEventStatus = $followingEvent->getStatus()->getValue();

                    if (
                        ($status === BookingStatus::APPROVED && $followingEventStatus === BookingStatus::REJECTED) ||
                        ($status === BookingStatus::REJECTED && $followingEventStatus === BookingStatus::APPROVED)
                    ) {
                        /** @var CustomerBooking $booking */
                        foreach ($followingEvent->getBookings()->getItems() as $booking) {
                            if (
                                $status === BookingStatus::REJECTED &&
                                $booking->getStatus()->getValue() === BookingStatus::APPROVED
                            ) {
                                $bookingRepository->updateStatusById(
                                    $booking->getId()->getValue(),
                                    BookingStatus::REJECTED
                                );

                                $booking->setChangedStatus(new BooleanValueObject(true));
                            }
                        }

                        $eventRepository->updateStatusById($followingEvent->getId()->getValue(), $status);

                        $followingEvent->setStatus(new BookingStatus($status));

                        $updatedEvents->addItem($followingEvent, $followingEvent->getId()->getValue());
                    }
                }
            }
        }

        return $updatedEvents;
    }

    /**
     * @param Event  $event
     * @param bool   $deleteFollowing
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws ContainerException
     */
    public function delete($event, $deleteFollowing)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var NotificationsToEntitiesRepository $notificationEntitiesRepo */
        $notificationEntitiesRepo = $this->container->get('domain.notificationEntities.repository');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        /** @var Collection $recurringEvents */
        $recurringEvents = $eventAS->getEventsByCriteria(
            [
                'parentId' => $event->getParentId() ?
                    $event->getParentId()->getValue() : $event->getId()->getValue()
            ],
            [
                'fetchEventsPeriods' => true,
                'fetchEventsTickets' => true,
                'fetchEventsTags'    => true,
                'fetchEventsImages'  => true,
                'fetchBookings'      => true,
                'fetchBookingsUsers' => true,
            ],
            0
        );

        $deletedEvents = new Collection();
        /** @var Event|null $newOriginRecurringEvent **/
        $newOriginRecurringEvent = null;

        $hasRecurringApprovedEvents = false;

        if (!$event->getRecurring()) {
            $notificationEntitiesRepo->removeIfOnly($event->getId()->getValue());
        }

        /** @var Event $recurringEvent */
        foreach ($recurringEvents->getItems() as $key => $recurringEvent) {
            // delete event
            if ($recurringEvent->getId()->getValue() === $event->getId()->getValue()) {
                $deletedEvents->addItem($recurringEvent);
                $this->deleteEvent($recurringEvent);
            }

            if ($recurringEvent->getId()->getValue() > $event->getId()->getValue()) {
                $recurringEventStatus = $recurringEvent->getStatus()->getValue();

                // delete following recurring events if they are canceled
                if ($deleteFollowing && $recurringEventStatus === BookingStatus::REJECTED) {
                    $deletedEvents->addItem($recurringEvent);
                    $this->deleteEvent($recurringEvent);
                }

                if ($recurringEventStatus === BookingStatus::APPROVED) {
                    $hasRecurringApprovedEvents = true;

                    // update following recurring events if they are approved and if origin event is deleted
                    if ($event->getParentId() === null) {
                        if ($newOriginRecurringEvent === null) {
                            $newOriginRecurringEvent = $recurringEvent;
                        }

                        $eventRepository->updateParentId(
                            $recurringEvent->getId()->getValue(),
                            $newOriginRecurringEvent->getId()->getValue() === $recurringEvent->getId()->getValue() ?
                                null :
                                $newOriginRecurringEvent->getId()->getValue()
                        );

                        $notificationEntitiesRepo->updateByEntityId($event->getId()->getValue(), $newOriginRecurringEvent->getId()->getValue(), 'entityId');
                    }
                }
            }
        }

        if ($deleteFollowing && $event->getRecurring() && !$hasRecurringApprovedEvents) {
            $notificationEntitiesRepo->removeIfOnly($event->getId()->getValue());
        }

        // update recurring time for previous recurring events if there are no following recurring events
        if (!$hasRecurringApprovedEvents) {
            /** @var Event $recurringEvent */
            foreach ($recurringEvents->getItems() as $key => $recurringEvent) {
                if ($recurringEvent->getId()->getValue() < $event->getId()->getValue()) {
                    $recurringEvent->getRecurring()->setUntil(
                        $event->getPeriods()->getItem(0)->getPeriodStart()
                    );

                    $this->updateSingle($recurringEvent, $recurringEvent, true);
                }
            }
        }

        return $deletedEvents;
    }

    /**
     * @param Event $event
     *
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    private function addSingle($event)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var EventPeriodsRepository $eventPeriodsRepository */
        $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

        /** @var EventTagsRepository $eventTagsRepository */
        $eventTagsRepository = $this->container->get('domain.booking.event.tag.repository');

        /** @var EventTicketRepository $eventTicketRepository */
        $eventTicketRepository = $this->container->get('domain.booking.event.ticket.repository');

        /** @var EventProvidersRepository $eventProvidersRepository */
        $eventProvidersRepository = $this->container->get('domain.booking.event.provider.repository');

        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');

        /** @var EventDomainService $eventDomainService */
        $eventDomainService = $this->container->get('domain.booking.event.service');

        $event->setStatus(new BookingStatus(BookingStatus::APPROVED));
        $event->setNotifyParticipants(1);
        $event->setCreated(new DateTimeValue(DateTimeService::getNowDateTimeObject()));

        $eventId = $eventRepository->add($event);

        $event->setId(new Id($eventId));

        /** @var EventPeriod $eventPeriod */
        foreach ($event->getPeriods()->getItems() as $eventPeriod) {
            $eventDomainService->fixPeriod(
                $eventPeriod->getPeriodStart()->getValue(),
                $eventPeriod->getPeriodEnd()->getValue()
            );

            $eventPeriod->setEventId(new Id($eventId));

            $eventPeriodId = $eventPeriodsRepository->add($eventPeriod);

            $eventPeriod->setId(new Id($eventPeriodId));
        }

        /** @var EventTag $eventTag */
        foreach ($event->getTags()->getItems() as $eventTag) {
            $eventTag->setEventId(new Id($eventId));

            $eventTagId = $eventTagsRepository->add($eventTag);

            $eventTag->setId(new Id($eventTagId));
        }

        /** @var EventTicket $eventTicket */
        foreach ($event->getCustomTickets()->getItems() as $eventTicket) {
            $eventTicket->setEventId(new Id($eventId));

            $eventTicketId = $eventTicketRepository->add($eventTicket);

            $eventTicket->setId(new Id($eventTicketId));
        }

        /** @var Provider $provider */
        foreach ($event->getProviders()->getItems() as $provider) {
            $eventProvidersRepository->add($event, $provider);
        }

        $galleryService->manageGalleryForEntityAdd($event->getGallery(), $event->getId()->getValue());
    }

    /**
     * @param Event $oldEvent
     * @param Event $newEvent
     * @param bool  $isPreviousEvent
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    private function updateSingle($oldEvent, $newEvent, $isPreviousEvent)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var EventPeriodsRepository $eventPeriodsRepository */
        $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

        /** @var EventTicketRepository $eventTicketRepository */
        $eventTicketRepository = $this->container->get('domain.booking.event.ticket.repository');

        /** @var EventProvidersRepository $eventProvidersRepository */
        $eventProvidersRepository = $this->container->get('domain.booking.event.provider.repository');

        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');

        /** @var EventDomainService $eventDomainService */
        $eventDomainService = $this->container->get('domain.booking.event.service');

        $eventId = $newEvent->getId()->getValue();

        if (!$isPreviousEvent) {
            /** @var EventTagsRepository $eventTagsRepository */
            $eventTagsRepository = $this->container->get('domain.booking.event.tag.repository');

            if ($oldEvent->getTags()->length()) {
                $eventTagsRepository->deleteByEventId($oldEvent->getId()->getValue());
            }

            /** @var EventTag $eventTag */
            foreach ($newEvent->getTags()->getItems() as $eventTag) {
                $eventTag->setEventId($newEvent->getId());

                $eventTagId = $eventTagsRepository->add($eventTag);

                $eventTag->setId(new Id($eventTagId));
            }

            $eventProvidersRepository->deleteByEventId($eventId);

            /** @var Provider $provider */
            foreach ($newEvent->getProviders()->getItems() as $provider) {
                $eventProvidersRepository->add($newEvent, $provider);
            }
        }

        $newEvent->setStatus($oldEvent->getStatus());

        $oldPeriodsIds = [];
        $newPeriodsIds = [];

        /** @var EventPeriod $eventPeriod */
        foreach ($oldEvent->getPeriods()->getItems() as $eventPeriod) {
            $oldPeriodsIds[] = $eventPeriod->getId()->getValue();
        }

        /** @var EventPeriod $eventPeriod */
        foreach ($newEvent->getPeriods()->getItems() as $eventPeriod) {
            $eventDomainService->fixPeriod(
                $eventPeriod->getPeriodStart()->getValue(),
                $eventPeriod->getPeriodEnd()->getValue()
            );

            if ($eventPeriod->getId()) {
                $newPeriodsIds[] = $eventPeriod->getId()->getValue();

                $eventPeriodsRepository->update($eventPeriod->getId()->getValue(), $eventPeriod);
            } else {
                $eventPeriodId = $eventPeriodsRepository->add($eventPeriod);

                $eventPeriod->setId(new Id($eventPeriodId));
            }
        }

        foreach (array_diff($oldPeriodsIds, $newPeriodsIds) as $eventPeriodId) {
            $eventPeriodsRepository->delete($eventPeriodId);
        }

        $oldEventTicketsIds = [];
        $newEventTicketsIds = [];

        /** @var EventTicket $eventTicket */
        foreach ($oldEvent->getCustomTickets()->getItems() as $eventTicket) {
            $oldEventTicketsIds[] = $eventTicket->getId()->getValue();
        }


        /** @var EventTicket $eventTicket */
        foreach ($newEvent->getCustomTickets()->getItems() as $eventTicket) {
            if ($eventTicket->getId() && $eventTicket->getId()->getValue() !== 0) {
                $newEventTicketsIds[] = $eventTicket->getId()->getValue();

                $eventTicket->setEventId(new Id($eventId));

                $eventTicketRepository->update($eventTicket->getId()->getValue(), $eventTicket);
            } else {
                $eventTicket->setEventId(new Id($eventId));

                $eventTicketId = $eventTicketRepository->add($eventTicket);

                $eventTicket->setId(new Id($eventTicketId));
            }
        }

        $ticketsIdsToDelete = array_diff($oldEventTicketsIds, $newEventTicketsIds);

        /** @var CustomerBooking $booking */
        foreach ($oldEvent->getBookings()->getItems() as $booking) {
            /** @var CustomerBookingEventTicket $ticketBooking */
            foreach ($booking->getTicketsBooking()->getItems() as $ticketBooking) {
                if (in_array($ticketBooking->getEventTicketId()->getValue(), $ticketsIdsToDelete)) {
                    $ticketsIdsToDelete = array_diff(
                        $ticketsIdsToDelete,
                        [$ticketBooking->getEventTicketId()->getValue()]
                    );
                }
            }
        }

        foreach ($ticketsIdsToDelete as $eventTicketId) {
            $eventTicketRepository->delete($eventTicketId);
        }

        $galleryService->manageGalleryForEntityUpdate($newEvent->getGallery(), $eventId, Entities::EVENT);

        $eventRepository->update($eventId, $newEvent);
    }

    /**
     * @param Event $newEvent
     * @param Event $oldEvent
     *
     * @return bool
     *
     */
    private function isSeparateRecurringEvent($newEvent, $oldEvent)
    {
        return $newEvent->getRecurring() && (
                $newEvent->getPeriods()->toArray() !== $oldEvent->getPeriods()->toArray() ||
                $newEvent->getRecurring()->getCycle()->getValue() !==
                ($oldEvent->getRecurring() ? $oldEvent->getRecurring()->getCycle()->getValue() : true)
            );
    }

    /**
     * @param Collection $providers
     * @param array      $dates
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function removeSlotsFromEvents($providers, $dates)
    {
        $providersIds = [];

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providersIds[] = $provider->getId()->getValue();
        }

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var Collection $events */
        $events = $eventRepository->getProvidersEvents(
            [
                'providers' => $providersIds,
                'dates'     => $dates,
                'status'    => BookingStatus::APPROVED,
            ]
        );

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            /** @var Provider $provider */
            foreach ($providers->getItems() as $provider) {
                if ($event->getProviders()->keyExists($provider->getId()->getValue())) {
                    /** @var EventPeriod $period */
                    foreach ($event->getPeriods()->getItems() as $period) {
                        $range = new \DatePeriod(
                            $period->getPeriodStart()->getValue(),
                            new \DateInterval('P1D'),
                            $period->getPeriodEnd()->getValue()
                        );

                        $eventStartTimeString = $period->getPeriodStart()->getValue()->format('H:i:s');

                        $eventEndTimeString = $period->getPeriodEnd()->getValue()->format('H:i:s');

                        /** @var \DateTime $date */
                        foreach ($range as $date) {
                            $eventStartString = $date->format('Y-m-d') . ' ' . $eventStartTimeString;

                            if ($eventEndTimeString === '00:00:00') {
                                $endDate = DateTimeService::getCustomDateTimeObject($date->format('Y-m-d') . ' 00:00:00');

                                $endDate->modify('+1 days')->setTime(0, 0, 0);

                                $eventEndString = $endDate->format('Y-m-d H:i:s');
                            } else {
                                $eventEndString = $date->format('Y-m-d') . ' ' . $eventEndTimeString;
                            }

                            /** @var Appointment $appointment */
                            $appointment = AppointmentFactory::create(
                                [
                                    'bookingStart'       => $eventStartString,
                                    'bookingEnd'         => $eventEndString,
                                    'notifyParticipants' => false,
                                    'serviceId'          => 0,
                                    'providerId'         => $provider->getId()->getValue(),
                                ]
                            );

                            $provider->getAppointmentList()->addItem($appointment);
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @param Event $event
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException|InvalidArgumentException
     */
    public function deleteEvent($event)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var EventPeriodsRepository $eventPeriodsRepository */
        $eventPeriodsRepository = $this->container->get('domain.booking.event.period.repository');

        /** @var EventTicketRepository $eventTicketRepository */
        $eventTicketRepository = $this->container->get('domain.booking.event.ticket.repository');

        /** @var EventProvidersRepository $eventProvidersRepository */
        $eventProvidersRepository = $this->container->get('domain.booking.event.provider.repository');

        /** @var TaxEntityRepository $taxEntityRepository */
        $taxEntityRepository = $this->container->get('domain.tax.entity.repository');

        /** @var CouponEventRepository $couponEventRepository */
        $couponEventRepository = $this->container->get('domain.coupon.event.repository');

        /** @var CustomFieldEventRepository $customFieldEventRepository */
        $customFieldEventRepository = $this->container->get('domain.customFieldEvent.repository');

        /** @var EventTagsRepository $eventTagsRepository */
        $eventTagsRepository = $this->container->get('domain.booking.event.tag.repository');

        /** @var GalleryApplicationService $galleryService */
        $galleryService = $this->container->get('application.gallery.service');

        /** @var CustomerBooking $booking */
        foreach ($event->getBookings()->getItems() as $booking) {
            if (!$this->deleteEventBooking($booking)) {
                return false;
            }
        }

        /** @var EventPeriod $eventPeriod */
        foreach ($event->getPeriods()->getItems() as $eventPeriod) {
            if (!$eventPeriodsRepository->delete($eventPeriod->getId()->getValue())) {
                return false;
            }
        }

        return
            $eventProvidersRepository->deleteByEntityId($event->getId()->getValue(), 'eventId') &&
            $taxEntityRepository->deleteByEntityIdAndEntityType($event->getId()->getValue(), 'event') &&
            $couponEventRepository->deleteByEntityId($event->getId()->getValue(), 'eventId') &&
            $customFieldEventRepository->deleteByEntityId($event->getId()->getValue(), 'eventId') &&
            $eventTagsRepository->deleteByEntityId($event->getId()->getValue(), 'eventId') &&
            $eventTicketRepository->deleteByEntityId($event->getId()->getValue(), 'eventId') &&
            $galleryService->manageGalleryForEntityDelete($event->getGallery()) &&
            $eventRepository->delete($event->getId()->getValue());
    }

    /**
     *
     * @param CustomerBooking $booking
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function deleteEventBooking($booking)
    {
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var CustomerBookingEventPeriodRepository $bookingEventPeriodRepository */
        $bookingEventPeriodRepository = $this->container->get('domain.booking.customerBookingEventPeriod.repository');

        /** @var CustomerBookingEventTicketRepository $bookingEventTicketRepository */
        $bookingEventTicketRepository = $this->container->get('domain.booking.customerBookingEventTicket.repository');

        /** @var Collection $payments */
        $payments = $paymentRepository->getByEntityId($booking->getId()->getValue(), 'customerBookingId');

        /** @var Payment $payment */
        foreach ($payments->getItems() as $payment) {
            if (!$paymentAS->delete($payment)) {
                return false;
            }
        }

        return
            $bookingEventTicketRepository->deleteByEntityId($booking->getId()->getValue(), 'customerBookingId') &&
            $bookingEventPeriodRepository->deleteByEntityId($booking->getId()->getValue(), 'customerBookingId') &&
            $bookingRepository->delete($booking->getId()->getValue());
    }

    /**
     * @param Collection|array $tickets
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     */
    public function getTicketsPriceByDateRange($tickets)
    {
        /** @var Collection $newTickets */
        $newTickets = new Collection();

        $ticketsCollection = new Collection();

        if (is_array($tickets)) {
            foreach ($tickets as $ticket) {
                $ticketsCollection->addItem(
                    EventTicketFactory::create(
                        $ticket
                    )
                );
            }
        } else {
            $ticketsCollection = $tickets;
        }

        /** @var EventTicket $ticket */
        foreach ($ticketsCollection->getItems() as $key => $ticket) {
            if ($ticket->getDateRanges()) {
                $ticketDateRanges = json_decode($ticket->getDateRanges()->getValue(), true);

                $currentDate = DateTimeService::getNowDateTimeObject();

                foreach ($ticketDateRanges as $range) {
                    $rangeStart = DateTimeService::getCustomDateTimeObject($range['startDate']);

                    $rangeEnd = DateTimeService::getCustomDateTimeObject($range['endDate'] . ' 23:59:59');

                    if ($currentDate > $rangeStart && $currentDate < $rangeEnd) {
                        $ticket->setDateRangePrice(new Price($range['price']));
                    }
                }

                $newTickets->placeItem($ticket, $key, true);
            }
        }

        return $newTickets;
    }

    /**
     * @param array $params
     * @param array $criteria
     * @param int   $limit
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getEventsByCriteria($params, $criteria, $limit)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        $eventsIds = $eventRepository->getFilteredIds($params, $limit);

        return $eventsIds ? $this->getEventsByIds(
            $eventsIds,
            $criteria
        ) : new Collection();
    }

    /**
     * @param array $ids
     * @param array $criteria
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getEventsByIds($ids, $criteria)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var Collection $events */
        $events = $eventRepository->getByIdsWithEntities(
            $ids,
            [
                'fetchEventsPeriods'   => !empty($criteria['fetchEventsPeriods']) ?
                    $criteria['fetchEventsPeriods'] : false,
                'fetchEventsTickets'   => !empty($criteria['fetchEventsTickets']) ?
                    $criteria['fetchEventsTickets'] : false,
                'fetchEventsTags'      => !empty($criteria['fetchEventsTags']) ?
                    $criteria['fetchEventsTags'] : false,
                'fetchEventsProviders' => !empty($criteria['fetchEventsProviders']) ?
                    $criteria['fetchEventsProviders'] : false,
                'fetchEventsImages'    => !empty($criteria['fetchEventsImages']) ?
                    $criteria['fetchEventsImages'] : false,
                'ordered'              => !empty($criteria['ordered']) ?
                    $criteria['ordered'] : false,
            ]
        );

        /** @var Collection $eventsBookings */
        $eventsBookings = $events->length() && !empty($criteria['fetchBookings']) ? $eventRepository->getBookingsByCriteria(
            [
                'ids'                   => $ids,
                'fetchBookingsTickets'  => !empty($criteria['fetchBookingsTickets']) ?
                    $criteria['fetchBookingsTickets'] : false,
                'fetchBookingsUsers'    => false,
                'fetchBookingsPayments' => !empty($criteria['fetchBookingsPayments']) ?
                    $criteria['fetchBookingsPayments'] : false,
                'fetchBookingsCoupons'  => !empty($criteria['fetchBookingsCoupons']) ?
                    $criteria['fetchBookingsCoupons'] : false,
                'fetchApprovedBookings' => !empty($criteria['fetchApprovedBookings']) ?
                    $criteria['fetchApprovedBookings'] : false,
            ]
        ) : new Collection();

        if (!empty($criteria['fetchBookingsUsers'])) {
            $customerIds = [];

            /** @var Collection $eventBookings */
            foreach ($eventsBookings->getItems() as $eventBookings) {
                /** @var CustomerBooking $customerBooking */
                foreach ($eventBookings->getItems() as $customerBooking) {
                    $customerIds[] = $customerBooking->getCustomerId()->getValue();
                }
            }

            /** @var Collection $customers */
            $customers = $customerIds ? $customerRepository->getByCriteria(['ids' => $customerIds]) : new Collection();

            /** @var Collection $eventBookings */
            foreach ($eventsBookings->getItems() as $eventBookings) {
                /** @var CustomerBooking $customerBooking */
                foreach ($eventBookings->getItems() as $customerBooking) {
                    /** @var Customer $customer */
                    $customer = $customers->getItem($customerBooking->getCustomerId()->getValue());

                    $customerBooking->setCustomer($customer);
                }
            }
        }

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            if ($eventsBookings->keyExists($event->getId()->getValue())) {
                $event->setBookings($eventsBookings->getItem($event->getId()->getValue()));
            }
        }

        return $events;
    }

    /**
     * @param int   $id
     * @param array $criteria
     *
     * @return Event
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getEventById($id, $criteria)
    {
        /** @var Collection $events */
        $events = $this->getEventsByIds(
            [$id],
            [
                'fetchEventsPeriods'    => !empty($criteria['fetchEventsPeriods']) ?
                    $criteria['fetchEventsPeriods'] : false,
                'fetchEventsTickets'    => !empty($criteria['fetchEventsTickets']) ?
                    $criteria['fetchEventsTickets'] : false,
                'fetchEventsTags'       => !empty($criteria['fetchEventsTags']) ?
                    $criteria['fetchEventsTags'] : false,
                'fetchEventsProviders'  => !empty($criteria['fetchEventsProviders']) ?
                    $criteria['fetchEventsProviders'] : false,
                'fetchEventsImages'     => !empty($criteria['fetchEventsImages']) ?
                    $criteria['fetchEventsImages'] : false,
                'fetchApprovedBookings' => !empty($criteria['fetchApprovedBookings']) ?
                    $criteria['fetchApprovedBookings'] : false,
                'fetchBookings'         => !empty($criteria['fetchBookings']) ?
                    $criteria['fetchBookings'] : false,
                'fetchBookingsTickets'  => !empty($criteria['fetchBookingsTickets']) ?
                    $criteria['fetchBookingsTickets'] : false,
                'fetchBookingsUsers'    => !empty($criteria['fetchBookingsUsers']) ?
                    $criteria['fetchBookingsUsers'] : false,
                'fetchBookingsPayments' => !empty($criteria['fetchBookingsPayments']) ?
                    $criteria['fetchBookingsPayments'] : false,
                'fetchBookingsCoupons'  => !empty($criteria['fetchBookingsCoupons']) ?
                    $criteria['fetchBookingsCoupons'] : false,
            ]
        );

        if ($events->length() && $events->keyExists($id)) {
            return $events->getItem($id);
        }

        return null;
    }

    /**
     * @param Collection $clonedEvents
     * @param Event $newEvent
     * @param boolean $updateFollowing
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException|InvalidArgumentException
     */
    private function getEditedEvents($clonedEvents, $newEvent, $updateFollowing, $followingEvents)
    {
        $clonedEditedEvents = [];
        if (
            $clonedEvents->keyExists($newEvent->getId()->getValue()) &&
            $this->eventDetailsUpdated($clonedEvents->getItem($newEvent->getId()->getValue()), $newEvent)
        ) {
            $clonedEditedEvents[$newEvent->getId()->getValue()] = clone $newEvent;
        }
        if ($updateFollowing && $followingEvents) {
            /** @var Event $event **/
            foreach ($clonedEvents->getItems() as $id => $event) {
                /** @var Event $changedEvent **/
                $changedEvent = $followingEvents->keyExists($id) ? $followingEvents->getItem($id) : null;
                if (
                    $changedEvent && $event->getId()->getValue() > $newEvent->getId()->getValue() &&
                    $this->eventDetailsUpdated($event, $changedEvent)
                ) {
                    $clonedEditedEvents[$event->getId()->getValue()] = $changedEvent;
                }
            }
        }
        return $clonedEditedEvents;
    }

    /**
     * @param Event $event
     * @param Event $newEvent
     *
     * @return bool
     *
     * @throws ContainerValueNotFoundException
     */
    private function eventDetailsUpdated($event, $newEvent)
    {
        return
            ($newEvent->getZoomUserId() ? $newEvent->getZoomUserId()->getValue() : null) !==
            ($event->getZoomUserId() ? $event->getZoomUserId()->getValue() : null) ||
            ($newEvent->getDescription() ? $newEvent->getDescription()->getValue() : null) !==
            ($event->getDescription() ? $event->getDescription()->getValue() : null) ||
            $newEvent->getName()->getValue() !== $event->getName()->getValue() ||
            ($newEvent->getLocationId() ? $newEvent->getLocationId()->getValue() : null) !==
            ($event->getLocationId() ? $event->getLocationId()->getValue() : null) ||
            ($newEvent->getCustomLocation() ? $newEvent->getCustomLocation()->getValue() : null) !==
            ($event->getCustomLocation() ? $event->getCustomLocation()->getValue() : null) ||
            ($newEvent->getOrganizerId() ? $newEvent->getOrganizerId()->getValue() : null) !==
            ($event->getOrganizerId() ? $event->getOrganizerId()->getValue() : null);
    }
}
