<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Reservation\EventReservationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventTicket;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Event\EventPeriodFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use DateTimeZone;
use Exception;

/**
 * Class GetEventsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventsCommandHandler extends CommandHandler
{
    /**
     * @param GetEventsCommand $command
     *
     * @return CommandResult
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function handle(GetEventsCommand $command)
    {
        $result = new CommandResult();

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var EventReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::EVENT);
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');
        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');
        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        $params = $command->getField('params');

        /** @var AbstractUser|null $user */
        $user = null;

        $isFrontEnd = isset($params['page']) && empty($params['group']);

        $isCalendarPage = $isFrontEnd && (int)$params['page'] === 0;

        $isCabinetPage = $command->getPage() === 'cabinet';

        if (!$isFrontEnd) {
            try {
                /** @var AbstractUser $user */
                $user = $command->getUserApplicationService()->authorization(
                    $isCabinetPage ? $command->getToken() : null,
                    $command->getCabinetType()
                );
            } catch (AuthorizationException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );

                return $result;
            }

            if ($userAS->isAmeliaUser($user) && $userAS->isCustomer($user)) {
                $params['customerId'] = $user->getId()->getValue();
            }

            if ($user && $user->getType() === AbstractUser::USER_ROLE_PROVIDER) {
                $params['providers'] = [$user->getId()->getValue()];
            }
        }

        if ($isFrontEnd && !empty($params['providers'])) {
            $params['providers'] = array_values($params['providers']);
        }

        if (isset($params['dates'][0])) {
            $params['dates'][0] ? $params['dates'][0] .= ' 00:00:00' : null;
        }

        if (isset($params['dates'][1])) {
            $params['dates'][1] ? $params['dates'][1] .= ' 23:59:59' : null;
        }

        if ($isFrontEnd) {
            $params['show'] = 1;

            if (!empty($params['tag'])) {
                $params['tag'] = str_replace('___', ' ', $params['tag']);
            }
        }

        /** @var Collection $events */
        $events = $eventAS->getEventsByCriteria(
            $params,
            [
                'fetchEventsPeriods'    => true,
                'fetchEventsTickets'    => true,
                'fetchEventsTags'       => $isFrontEnd,
                'fetchEventsProviders'  => ($isFrontEnd || $command->getPage() === 'calendar'),
                'fetchEventsImages'     => $isFrontEnd,
                'fetchBookings'         => true,
                'fetchBookingsTickets'  => true,
                'fetchBookingsCoupons'  => $isCabinetPage,
                'fetchBookingsPayments' => $isCabinetPage,
                'fetchBookingsUsers'    => $isCabinetPage,
            ],
            $isFrontEnd
                ? (!empty($params['limit']) ? $params['limit'] : $settingsDS->getSetting('general', 'itemsPerPage'))
                : $settingsDS->getSetting('general', 'itemsPerPageBackEnd')
        );

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $eventsArray = [];

        $customersNoShowCountIds = [];

        $noShowTagEnabled = $settingsDS->getSetting('roles', 'enableNoShowTag');

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            if ($isFrontEnd && !$event->getShow()->getValue()) {
                continue;
            }

            $persons = 0;

            if ($event->getCustomPricing()->getValue()) {
                /** @var CustomerBooking $booking */
                foreach ($event->getBookings()->getItems() as $booking) {
                    /** @var CustomerBookingEventTicket $bookedTicket */
                    foreach ($booking->getTicketsBooking()->getItems() as $bookedTicket) {
                        /** @var EventTicket $ticket */
                        $ticket = $event->getCustomTickets()->getItem($bookedTicket->getEventTicketId()->getValue());

                        $ticket->setSold(
                            new IntegerValue(
                                ($ticket->getSold() ? $ticket->getSold()->getValue() : 0) +
                                ($booking->getStatus()->getValue() === BookingStatus::APPROVED || $booking->getStatus()->getValue() === BookingStatus::PENDING ?
                                    $bookedTicket->getPersons()->getValue() : 0)
                            )
                        );

                        $ticket->setWaiting(
                            new IntegerValue(
                                ($ticket->getWaiting() ? $ticket->getWaiting()->getValue() : 0) +
                                ($booking->getStatus()->getValue() === BookingStatus::WAITING ?
                                    $bookedTicket->getPersons()->getValue() : 0)
                            )
                        );
                    }

                    if ($noShowTagEnabled) {
                        $customersNoShowCountIds[] = $booking->getCustomerId()->getValue();
                    }
                }

                $maxCapacity = 0;

                $event->setCustomTickets($eventAS->getTicketsPriceByDateRange($event->getCustomTickets()));

                /** @var EventTicket $ticket */
                foreach ($event->getCustomTickets()->getItems() as $ticket) {
                    $maxCapacity += $ticket->getSpots()->getValue();

                    $persons += ($ticket->getSold() ? $ticket->getSold()->getValue() : 0);
                }

                $event->setMaxCapacity($event->getMaxCustomCapacity() ?: new IntegerValue($maxCapacity));
            } else {
                /** @var CustomerBooking $booking */
                foreach ($event->getBookings()->getItems() as $booking) {
                    if ($booking->getStatus()->getValue() === BookingStatus::APPROVED || $booking->getStatus()->getValue() === BookingStatus::PENDING) {
                        $persons += $booking->getPersons()->getValue();
                    }

                    if ($noShowTagEnabled) {
                        $customersNoShowCountIds[] = $booking->getCustomerId()->getValue();
                    }
                }
            }

            if (
                ($isFrontEnd && $settingsDS->getSetting('general', 'showClientTimeZone')) ||
                $isCabinetPage || ($user && $user->getType() === AbstractUser::USER_ROLE_PROVIDER)
            ) {
                $timeZone = 'UTC';

                if (!empty($params['timeZone'])) {
                    $timeZone = $params['timeZone'];
                } elseif (
                    $user instanceof Provider &&
                    empty($user->getTimeZone()) && !empty(get_option('timezone_string'))
                ) {
                    $timeZone = get_option('timezone_string');
                }

                /** @var EventPeriod $period */
                foreach ($event->getPeriods()->getItems() as $period) {
                    $period->getPeriodStart()->getValue()->setTimezone(new DateTimeZone($timeZone));
                    $period->getPeriodEnd()->getValue()->setTimezone(new DateTimeZone($timeZone));
                }
            }

            $bookingOpens = $event->getBookingOpens() ?
                $event->getBookingOpens()->getValue() : $event->getCreated()->getValue();

            $bookingCloses = $event->getBookingCloses() ?
                $event->getBookingCloses()->getValue() : $event->getPeriods()->getItem(0)->getPeriodStart()->getValue();

            $minimumCancelTimeInSeconds = $settingsDS
                ->getEntitySettings($event->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToCanceling();

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $event->getPeriods()->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$minimumCancelTimeInSeconds} seconds");

            $minimumReached = null;
            if ($event->getCloseAfterMin() !== null && $event->getCloseAfterMinBookings() !== null) {
                if ($event->getCloseAfterMinBookings()->getValue()) {
                    $approvedBookings = array_filter(
                        $event->getBookings()->toArray(),
                        function ($value) {
                            return $value['status'] === 'approved';
                        }
                    );
                    $minimumReached   = count($approvedBookings) >= $event->getCloseAfterMin()->getValue();
                } else {
                    $minimumReached = $persons >= $event->getCloseAfterMin()->getValue();
                }
            }

            $peopleWaiting = false;
            $eventSettings = $event->getSettings() ? json_decode($event->getSettings()->getValue(), true) : null;

            if ($eventSettings && !empty($eventSettings['waitingList']) && $eventSettings['waitingList']['enabled']) {
                foreach ($event->getBookings()->getItems() as $booking) {
                    if ($booking->getStatus()->getValue() === BookingStatus::WAITING) {
                        $peopleWaiting = true;
                        break;
                    }
                }
            }

            $eventsInfo = [
                'bookable'   => $reservationService->isBookable($event, null, $currentDateTime) && !$minimumReached,
                'cancelable' =>
                    $currentDateTime <= $minimumCancelTime &&
                    (
                        $event->getStatus()->getValue() === BookingStatus::APPROVED ||
                        $event->getStatus()->getValue() === BookingStatus::PENDING
                    ),
                'opened'     => ($currentDateTime > $bookingOpens) && ($currentDateTime < $bookingCloses),
                'closed'     => $currentDateTime > $bookingCloses || $minimumReached,
                'places'     => $event->getMaxCapacity()->getValue() - $persons,
                'upcoming'   => $currentDateTime < $bookingOpens && $event->getStatus()->getValue() === BookingStatus::APPROVED,
                'full'       => ($event->getMaxCapacity()->getValue() <= $persons
                                  && $currentDateTime < $event->getPeriods()->getItem(0)->getPeriodStart()->getValue())
                                  || ($peopleWaiting && !($currentDateTime > $bookingCloses || $minimumReached))
            ];

            if ($isFrontEnd) {
                $event->setBookings(new Collection());

                /** @var EventPeriod $eventPeriod */
                foreach ($event->getPeriods()->getItems() as $key => $eventPeriod) {
                    /** @var EventPeriod $newEventPeriod **/
                    $newEventPeriod = EventPeriodFactory::create(
                        array_merge(
                            $eventPeriod->toArray(),
                            ['zoomMeeting' => null]
                        )
                    );

                    $event->getPeriods()->placeItem($newEventPeriod, $key, true);
                }
            }

            $ameliaUserId = $userAS->isAmeliaUser($user) && $user->getId() ? $user->getId()->getValue() : null;

            // Delete other bookings if user is customer
            if ($userAS->isCustomer($user)) {
                /** @var CustomerBooking $booking */
                foreach ($event->getBookings()->getItems() as $bookingKey => $booking) {
                    if ($booking->getCustomerId()->getValue() !== $ameliaUserId) {
                        $event->getBookings()->deleteItem($bookingKey);
                    }
                }
            }

            if (!$isFrontEnd && $userAS->isCustomer($user) && $event->getBookings()->length() === 0) {
                continue;
            }

            $eventsArray[] = array_merge($event->toArray(), $eventsInfo);
        }

        $customersNoShowCount = [];

        if ($noShowTagEnabled && $customersNoShowCountIds) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            $customersNoShowCount = $bookingRepository->countByNoShowStatus($customersNoShowCountIds);
        }

        $eventsArray = apply_filters('amelia_get_events_filter', $eventsArray);

        do_action('amelia_get_events', $eventsArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved events');
        $result->setData(
            [
                Entities::EVENTS       => $eventsArray,
                'count'                => !$isCalendarPage && empty($params['skipCount']) ? (int)$eventRepository->getFilteredIdsCount($params) : null,
                'customersNoShowCount' => $customersNoShowCount
            ]
        );

        return $result;
    }
}
