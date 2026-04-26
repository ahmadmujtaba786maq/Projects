<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\Deposit\AbstractDepositApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingsLimitReachedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventTicket;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\CustomerBookingEventPeriodFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\CustomerBookingEventTicketFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\AmountType;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventPeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventTicketRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use DateTime;
use Exception;
use Slim\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class EventReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class EventReservationService extends AbstractReservationService
{
    /**
     * @return string
     */
    public function getType()
    {
        return Entities::EVENT;
    }

    /**
     * @param array       $eventData
     * @param Reservation $reservation
     * @param bool        $save
     *
     * @return void
     *
     * @throws CouponExpiredException
     * @throws CouponInvalidException
     * @throws CouponUnknownException
     * @throws BookingUnavailableException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function book($eventData, $reservation, $save)
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');

        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        $this->manageTaxes($eventData);

        /** @var Coupon $coupon */
        $coupon = !empty($eventData['couponCode']) ? $couponAS->processCoupon(
            $eventData['couponCode'],
            [$eventData['eventId']],
            Entities::EVENT,
            $eventData['bookings'][0]['customerId'],
            $reservation->hasCouponValidation()->getValue()
        ) : null;

        if ($coupon) {
            $eventData['bookings'][0]['coupon'] = $coupon->toArray();

            $eventData['bookings'][0]['couponId'] = $coupon->getId()->getValue();
        }

        /** @var Event $event */
        $event = $eventApplicationService->getEventById(
            $eventData['eventId'],
            [
                'fetchEventsPeriods'    => true,
                'fetchEventsTickets'    => true,
                'fetchApprovedBookings' => true,
                'fetchBookings'         => true,
                'fetchBookingsTickets'  => true,
                'fetchEventsProviders'  => true,
            ]
        );

        if ($event->getCustomPricing()->getValue()) {
            $event->setCustomTickets($eventApplicationService->getTicketsPriceByDateRange($event->getCustomTickets()));
        }

        $bookingArray =  array_merge(
            $eventData['bookings'][0],
            empty($eventData['bookings'][0]['status']) ?
            ['status' => BookingStatus::APPROVED] : ['status' => $eventData['bookings'][0]['status']]
        );

        $bookingArray = apply_filters('amelia_before_event_booking_saved_filter', $bookingArray, $event ? $event->toArray() : null);

        do_action('amelia_before_event_booking_saved', $bookingArray, $event ? $event->toArray() : null);

        $booking = CustomerBookingFactory::create($bookingArray);

        if ($event->getCustomPricing()->getValue()) {
            $booking->setPersons(new IntegerValue(0));
        }

        $bookingStatus = empty($eventData['bookings'][0]['status']) ? BookingStatus::APPROVED : $eventData['bookings'][0]['status'];

        if (!empty($eventData['payment']['gateway'])) {
            $bookingStatus = in_array($eventData['payment']['gateway'], [PaymentType::MOLLIE]) ?
                BookingStatus::PENDING : (empty($eventData['bookings'][0]['status']) ? BookingStatus::APPROVED : $eventData['bookings'][0]['status']);

            if (!empty($eventData['payment']['orderStatus'])) {
                $bookingStatus = $this->getWcStatus(
                    Entities::EVENT,
                    $eventData['payment']['orderStatus'],
                    'booking',
                    false
                ) ?: $bookingStatus;
            }
        }

        $booking->setStatus(new BookingStatus($bookingStatus));

        $personsCount = 0;

        /** @var CustomerBooking $customerBooking */
        foreach ($event->getBookings()->getItems() as $customerBooking) {
            if ($customerBooking->getStatus()->getValue() === BookingStatus::APPROVED) {
                $personsCount += $customerBooking->getPersons()->getValue();
            }
            if (
                $customerBooking->getStatus()->getValue() !== BookingStatus::CANCELED &&
                !$event->getBookMultipleTimes()->getValue() &&
                $booking->getCustomerId() &&
                $booking->getCustomerId()->getValue() === $customerBooking->getCustomerId()->getValue()
            ) {
                throw new CustomerBookedException(
                    FrontendStrings::getCommonStrings()['customer_already_booked_ev']
                );
            }
        }

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $limitPerCustomerEvents = $settingsDS->getSetting('roles', 'limitPerCustomerEvent');

        if (
            !empty($limitPerCustomerEvents) &&
            $limitPerCustomerEvents['enabled'] &&
            empty($eventData['isBackendOrCabinet'])
        ) {
            /** @var EventRepository $eventRepository */
            $eventRepository = $this->container->get('domain.booking.event.repository');

            $count = $eventRepository->getRelevantBookingsCount(
                $event,
                $booking->toArray(),
                $limitPerCustomerEvents
            );

            if ($count >= $limitPerCustomerEvents['numberOfApp']) {
                throw new BookingsLimitReachedException(
                    FrontendStrings::getCommonStrings()['bookings_limit_reached']
                );
            }
        }

        /** @var AbstractUser $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        $isCustomer = (!$currentUser || ($currentUser->getType() === AbstractUser::USER_ROLE_CUSTOMER));

        $isProvider =
            $reservation->getLoggedInUser() &&
            $reservation->getLoggedInUser()->getType() === AbstractUser::USER_ROLE_PROVIDER;

        if (
            $reservation->hasAvailabilityValidation()->getValue() &&
            $isCustomer &&
            !$isProvider &&
            !$this->isBookable($event, $booking, DateTimeService::getNowDateTimeObject())
        ) {
            throw new BookingUnavailableException(
                FrontendStrings::getCommonStrings()['time_slot_unavailable']
            );
        }


        $booking->setAggregatedPrice(new BooleanValueObject($event->getAggregatedPrice() ? $event->getAggregatedPrice()->getValue() : true));

        $paymentAmount = $this->getPaymentAmount($booking, $event)['price'];

        $applyDeposit =
            !empty($eventData['bookings'][0]['deposit']) && $eventData['payment']['gateway'] !== PaymentType::ON_SITE;

        if ($applyDeposit) {
            $personsCount = $booking->getPersons()->getValue();

            if ($booking->getTicketsBooking() && $event->getCustomPricing()->getValue()) {
                $personsCount = 0;

                /** @var CustomerBookingEventTicket $bookingToEventTicket */
                foreach ($booking->getTicketsBooking()->getItems() as $bookingToEventTicket) {
                    $personsCount += ($bookingToEventTicket->getPersons() ?
                        $bookingToEventTicket->getPersons()->getValue() : 0);
                }
            }

            $paymentDeposit = $depositAS->calculateDepositAmount(
                $paymentAmount,
                $event,
                $personsCount
            );

            $eventData['payment']['deposit'] = $paymentAmount !== $paymentDeposit;

            $paymentAmount = $paymentDeposit;
        }

        if ($save) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            /** @var CustomerBookingExtraRepository $bookingExtraRepository */
            $bookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');

            /** @var CustomerBookingEventPeriodRepository $bookingEventPeriodRepository */
            $bookingEventPeriodRepository =
                $this->container->get('domain.booking.customerBookingEventPeriod.repository');

            /** @var CustomerBookingEventTicketRepository $bookingEventTicketRepository */
            $bookingEventTicketRepository = $this->container->get('domain.booking.customerBookingEventTicket.repository');

            $booking->setPrice(new Price($event->getPrice()->getValue()));
            $booking->setToken(new Token());

            if ($booking->getTicketsBooking() && $event->getCustomPricing()->getValue()) {
                $ticketSumPrice = 0;

                /** @var CustomerBookingEventTicket $bookingToEventTicket */
                foreach ($booking->getTicketsBooking()->getItems() as $bookingToEventTicket) {
                    /** @var EventTicket $ticket */
                    $ticket = $event->getCustomTickets()->getItem(
                        $bookingToEventTicket->getEventTicketId()->getValue()
                    );

                    $ticketPrice = $ticket->getDateRangePrice() ?
                        $ticket->getDateRangePrice()->getValue() : $ticket->getPrice()->getValue();

                    $ticketSumPrice += $bookingToEventTicket->getPersons() ?
                        ($booking->getAggregatedPrice()->getValue() ? $bookingToEventTicket->getPersons()->getValue() : 1)
                         * $ticketPrice : 0;
                }

                $booking->setPrice(new Price($ticketSumPrice));
            }

            $booking->setActionsCompleted(new BooleanValueObject(!empty($eventData['payment']['isBackendBooking'])));

            $bookingId = $bookingRepository->add($booking);

            /** @var CustomerBookingExtra $bookingExtra */
            foreach ($booking->getExtras()->getItems() as $bookingExtra) {
                $bookingExtra->setCustomerBookingId(new Id($bookingId));
                $bookingExtraId = $bookingExtraRepository->add($bookingExtra);
                $bookingExtra->setId(new Id($bookingExtraId));
            }

            $booking->setId(new Id($bookingId));

            /** @var Payment $payment */
            $payment = $this->addPayment(
                $booking->getId()->getValue(),
                null,
                $eventData['payment'],
                $paymentAmount,
                $event->getPeriods()->getItem(0)->getPeriodStart()->getValue(),
                Entities::EVENT
            );

            /** @var Collection $payments */
            $payments = new Collection();

            $payments->addItem($payment);

            $booking->setPayments($payments);

            /** @var EventPeriod $eventPeriod */
            foreach ($event->getPeriods()->getItems() as $eventPeriod) {
                /** @var CustomerBookingEventPeriod $bookingEventPeriod */
                $bookingEventPeriod = CustomerBookingEventPeriodFactory::create(
                    [
                        'eventPeriodId'     => $eventPeriod->getId()->getValue(),
                        'customerBookingId' => $bookingId
                    ]
                );

                $bookingEventPeriodRepository->add($bookingEventPeriod);
            }

            /** @var CustomerBookingEventTicket $eventTicket */
            foreach ($booking->getTicketsBooking()->getItems() as $eventTicket) {
                if ($eventTicket->getPersons()) {
                    /** @var EventTicket $ticket */
                    $ticket = $event->getCustomTickets()->getItem($eventTicket->getEventTicketId()->getValue());

                    $ticketPrice = $ticket->getDateRangePrice() ?
                        $ticket->getDateRangePrice()->getValue() : $ticket->getPrice()->getValue();

                    /** @var CustomerBookingEventTicket $bookingEventTicket */
                    $bookingEventTicket = CustomerBookingEventTicketFactory::create(
                        [
                            'eventTicketId'     => $eventTicket->getEventTicketId()->getValue(),
                            'customerBookingId' => $bookingId,
                            'persons'           => $eventTicket->getPersons()->getValue(),
                            'price'             => $ticketPrice,
                        ]
                    );

                    $bookingEventTicketRepository->add($bookingEventTicket);
                }
            }

            $event->getBookings()->addItem($booking, $booking->getId()->getValue());

            do_action('amelia_after_event_booking_saved', $booking ? $booking->toArray() : null, $event ? $event->toArray() : null);
        }

        if ($event->getLocationId()) {
            /** @var Location $location */
            $location = $locationRepository->getById($event->getLocationId()->getValue());

            $event->setLocation($location);
        }

        $reservation->setApplyDeposit(new BooleanValueObject($applyDeposit));
        if ($booking->getCustomer()) {
            $reservation->setCustomer($booking->getCustomer());
        }

        /** @var Collection $bookings */
        $bookings = new Collection();

        $bookings->addItem($booking);

        $event->setBookings($bookings);

        $reservation->setBookable($event);
        $reservation->setBooking($booking);
        $reservation->setReservation($event);
        $reservation->setRecurring(new Collection());
        $reservation->setPackageReservations(new Collection());
        $reservation->setIsStatusChanged(new BooleanValueObject(false));
    }

    /**
     * @param CustomerBooking $booking
     * @param string          $requestedStatus
     *
     * @return array
     *
     * @throws ContainerException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws BookingCancellationException
     */
    public function updateStatus($booking, $requestedStatus)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var Event $event */
        $event = $this->getReservationByBookingId($booking->getId()->getValue());

        if ($requestedStatus === BookingStatus::CANCELED) {
             $minimumCancelTimeInSeconds = $settingsDS
                ->getEntitySettings($event->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToCanceling();

            $this->inspectMinimumCancellationTime(
                $event->getPeriods()->getItem(0)->getPeriodStart()->getValue(),
                $minimumCancelTimeInSeconds
            );
        }

        $booking->setStatus(new BookingStatus($requestedStatus));

        $bookingRepository->update($booking->getId()->getValue(), $booking);

        return [
            Entities::EVENT            => $event->toArray(),
            'appointmentStatusChanged' => false,
            Entities::BOOKING          => $booking->toArray()
        ];
    }

    /**
     * @param Event            $reservation
     * @param CustomerBooking  $booking
     * @param AbstractBookable $bookable
     *
     * @return array
     */
    public function getBookingPeriods($reservation, $booking, $bookable)
    {
        $dates = [];

        /** @var EventPeriod $period */
        foreach ($reservation->getPeriods()->getItems() as $period) {
            $dates[] = [
                'start' => DateTimeService::getCustomDateTimeInUtc(
                    $period->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
                ),
                'end'   => DateTimeService::getCustomDateTimeInUtc(
                    $period->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
                )
            ];
        }

        return $dates;
    }

    /**
     * @param array $data
     *
     * @return AbstractBookable
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getBookableEntity($data)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        return $eventRepository->getById($data['eventId']);
    }

    /**
     * @param Event $bookable
     *
     * @return boolean
     */
    public function isAggregatedPrice($bookable)
    {
        return $bookable->getAggregatedPrice()->getValue();
    }

    /**
     * @param BooleanValueObject $bookableAggregatedPrice
     * @param BooleanValueObject $extraAggregatedPrice
     *
     * @return boolean
     */
    public function isExtraAggregatedPrice($extraAggregatedPrice, $bookableAggregatedPrice)
    {
        return true;
    }

    /**
     * @param Reservation $reservation
     * @param string      $paymentGateway
     * @param array       $requestData
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getWooCommerceData($reservation, $paymentGateway, $requestData)
    {
        /** @var Event $event */
        $event = $reservation->getBookable();

        /** @var AbstractUser $customer */
        $customer = $reservation->getCustomer();

        /** @var CustomerBooking $booking */
        $booking = $reservation->getBooking();

        $dateTimeValues = [];

        /** @var EventPeriod $period */
        foreach ($event->getPeriods()->getItems() as $period) {
            $dateTimeValues[] = [
                'start' => $period->getPeriodStart()->getValue()->format('Y-m-d H:i'),
                'end'   => $period->getPeriodEnd()->getValue()->format('Y-m-d H:i')
            ];
        }

        $info = [
            'type'               => Entities::EVENT,
            'eventId'            => $event->getId()->getValue(),
            'name'               => $event->getName()->getValue(),
            'couponId'           => $booking->getCoupon() ? $booking->getCoupon()->getId()->getValue() : '',
            'couponCode'         => $booking->getCoupon() ? $booking->getCoupon()->getCode()->getValue() : '',
            'dateTimeValues'     => $dateTimeValues,
            'bookings'           => [
                [
                    'customerId'   => $customer->getId() ? $customer->getId()->getValue() : null,
                    'customer'     => [
                        'email'           => $customer->getEmail()->getValue(),
                        'externalId'      => $customer->getExternalId() ? $customer->getExternalId()->getValue() : null,
                        'firstName'       => $customer->getFirstName()->getValue(),
                        'id'              => $customer->getId() ? $customer->getId()->getValue() : null,
                        'lastName'        => $customer->getLastName()->getValue(),
                        'phone'           => $customer->getPhone()->getValue(),
                        'countryPhoneIso' => $customer->getCountryPhoneIso() ?
                            $customer->getCountryPhoneIso()->getValue() : null,
                        'customFields'    => $customer->getCustomFields() ?
                            json_decode($customer->getCustomFields()->getValue(), true) : null,
                    ],
                    'info'         => $booking->getInfo()->getValue(),
                    'persons'      => $booking->getPersons()->getValue(),
                    'extras'       => [],
                    'utcOffset'    => $booking->getUtcOffset() ? $booking->getUtcOffset()->getValue() : null,
                    'customFields' => $booking->getCustomFields() ?
                        json_decode($booking->getCustomFields()->getValue(), true) : null,
                    'deposit'      => $reservation->getApplyDeposit()->getValue(),
                    'ticketsData'  => $requestData['bookings'][0]['ticketsData'],
                ]
            ],
            'payment'            => [
                'gateway' => $paymentGateway
            ],
            'locale'             => $reservation->getLocale()->getValue(),
            'timeZone'           => $reservation->getTimeZone()->getValue(),
            'recurring'          => [],
            'package'            => [],
        ];

        foreach ($booking->getExtras()->keys() as $extraKey) {
            /** @var CustomerBookingExtra $bookingExtra */
            $bookingExtra = $booking->getExtras()->getItem($extraKey);

            $info['bookings'][0]['extras'][] = [
                'extraId'  => $bookingExtra->getExtraId()->getValue(),
                'quantity' => $bookingExtra->getQuantity()->getValue()
            ];
        }

        return $info;
    }


    /**
     * @param array $reservation
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getWooCommerceDataFromArray($reservation, $index)
    {
        /** @var array $event */
        $event = $reservation['bookable'];

        /** @var array $customer */
        $customer = !empty($reservation['customer'])
            ? $reservation['customer']
            : (!empty($reservation['booking']['customer']) ? $reservation['booking']['customer'] : null);

        /** @var array $booking */
        $booking = $reservation['booking'];

        if (!empty($booking['ticketsData'])) {
            foreach ($booking['ticketsData'] as &$ticket) {
                $customTicketIndex = array_search(
                    $ticket['eventTicketId'],
                    array_column($reservation['event']['customTickets'], 'id')
                );
                if ($customTicketIndex !== false) {
                    $ticket['name'] = $reservation['event']['customTickets'][$customTicketIndex]['name'];
                }
            }
        }

        $dateTimeValues = [];

        $customerInfo = !empty($booking['info']) ? json_decode($booking['info'], true) : null;

        /** @var EventPeriod $period */
        foreach ($event['periods'] as $period) {
            $dateTimeValues[] = [
                'start' => $period['periodStart'],
                'end'   => $period['periodEnd']
            ];
        }

        $info = [
            'type'               => Entities::EVENT,
            'eventId'            => $event['id'],
            'name'               => $event['name'],
            'couponId'           => $booking['coupon'] ? $booking['coupon']['id'] : '',
            'couponCode'         => $booking['coupon'] ? $booking['coupon']['code'] : '',
            'dateTimeValues'     => $dateTimeValues,
            'bookings'           => [
                [
                    'customerId'   => $customer['id'],
                    'customer'     => [
                        'email'           => $customer['email'],
                        'externalId'      => $customer['externalId'],
                        'firstName'       => $customer['firstName'],
                        'id'              => $customer['id'],
                        'lastName'        => $customer['lastName'],
                        'phone'           => $customer['phone'],
                        'countryPhoneIso' => $customer['countryPhoneIso']
                    ],
                    'info'         => $booking['info'],
                    'persons'      => $booking['persons'],
                    'extras'       => [],
                    'utcOffset'    => $booking['utcOffset'],
                    'customFields' => $booking['customFields'] ?
                        json_decode($booking['customFields'], true) : null,
                    'deposit'      => $booking['price'] > $booking['payments'][0]['amount'],
                    'ticketsData'  => $booking['ticketsData'],
                ]
            ],
            'payment'            => [
                'gateway' => $booking['payments'][0]['gateway']
            ],
            'locale'             => $customerInfo ? $customerInfo['locale'] : null,
            'timeZone'           => $customerInfo ? $customerInfo['timeZone'] : null,
            'recurring'          => [],
            'package'            => [],
        ];

        foreach ($booking['extras'] as $extra) {
            $info['bookings'][0]['extras'][] = [
                'extraId'  => $extra['id'],
                'quantity' => $extra['quantity']
            ];
        }

        return $info;
    }

    /**
     * @param int $id
     *
     * @return Event
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getReservationByBookingId($id)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var Event $event */
        $event = $eventRepository->getByBookingId(
            $id,
            [
                'fetchEventsTickets'   => true,
                'fetchEventsTags'      => true,
                'fetchEventsProviders' => true,
                'fetchEventsImages'    => true,
            ]
        );

        /** @var Collection $eventsBookings */
        $eventsBookings = $eventRepository->getBookingsByCriteria(
            [
                'ids'                   => [$event->getId()->getValue()],
                'fetchBookings'         => true,
                'fetchBookingsTickets'  => true,
                'fetchBookingsUsers'    => true,
                'fetchBookingsPayments' => true,
            ]
        );

        if ($eventsBookings->keyExists($event->getId()->getValue())) {
            $event->setBookings($eventsBookings->getItem($event->getId()->getValue()));
        }

        return $event;
    }

    /**
     * @param Event           $reservation
     * @param CustomerBooking $newBooking
     * @param DateTime        $dateTime
     *
     * @return boolean
     *
     * @throws InvalidArgumentException
     */
    public function isBookable($reservation, $newBooking, $dateTime)
    {
        if ($reservation->getCustomPricing() && $reservation->getCustomPricing()->getValue() && !$reservation->getMaxCustomCapacity()) {
            $availableTicketsSpots = [];

            /** @var EventTicket $ticket */
            foreach ($reservation->getCustomTickets()->getItems() as $ticket) {
                $availableTicketsSpots[$ticket->getId()->getValue()] = $ticket->getSpots()->getValue();
            }

            $reservedTicketsSpots = [];

            /** @var CustomerBooking $booking */
            foreach ($reservation->getBookings()->getItems() as $booking) {
                if ($booking->getStatus()->getValue() === BookingStatus::APPROVED) {
                    /** @var CustomerBookingEventTicket $bookingTicket */
                    foreach ($booking->getTicketsBooking()->getItems() as $bookingTicket) {
                        $eventTicketId = $bookingTicket->getEventTicketId()->getValue();

                        if (!array_key_exists($eventTicketId, $reservedTicketsSpots)) {
                            $reservedTicketsSpots[$eventTicketId] = 0;
                        }

                        $reservedTicketsSpots[$eventTicketId] += $bookingTicket->getPersons()->getValue();
                    }
                }
            }

            if ($newBooking) {
                /** @var CustomerBookingEventTicket $newBookingTicket */
                foreach ($newBooking->getTicketsBooking()->getItems() as $newBookingTicket) {
                    $eventTicketId = $newBookingTicket->getEventTicketId()->getValue();

                    if (empty($reservedTicketsSpots[$eventTicketId])) {
                        $reservedTicketsSpots[$eventTicketId] = 0;
                    }

                    $reservedTicketsSpots[$eventTicketId] +=
                        $newBookingTicket->getPersons() ? $newBookingTicket->getPersons()->getValue() : 0;
                }
            }

            $hasTicketCapacity = [];

            foreach ($availableTicketsSpots as $eventTicketId => $availablePersons) {
                $hasTicketCapacity[$eventTicketId] = array_key_exists($eventTicketId, $reservedTicketsSpots) ?
                    ($newBooking ?
                        $reservedTicketsSpots[$eventTicketId] <= $availablePersons :
                        $reservedTicketsSpots[$eventTicketId] < $availablePersons
                    ) : true;
            }

            $hasCapacity = false;

            foreach ($hasTicketCapacity as $ticketId => $ticketCapacity) {
                if ($newBooking) {
                    $hasCapacity = true;

                    /** @var EventTicket $ticket */
                    $ticket = $reservation->getCustomTickets()->getItem($ticketId);

                    /** @var CustomerBookingEventTicket $ticketBooking */
                    foreach ($newBooking->getTicketsBooking()->getItems() as $ticketBooking) {
                        if (
                            $ticketBooking->getEventTicketId()->getValue() === $ticketId &&
                            $ticketBooking->getPersons() &&
                            $ticketBooking->getPersons()->getValue() &&
                            (!$ticketCapacity || !$ticket->getEnabled()->getValue())
                        ) {
                            $hasCapacity = false;

                            break 2;
                        }
                    }
                } else {
                    $hasCapacity = $hasCapacity || $ticketCapacity;
                }
            }
        } elseif ($reservation->getMaxCustomCapacity()) {
            $availableTicketsSpots = $reservation->getMaxCustomCapacity()->getValue();
            $reservedTicketsSpots  = 0;
            /** @var CustomerBooking $booking */
            foreach ($reservation->getBookings()->getItems() as $booking) {
                if ($booking->getStatus()->getValue() === BookingStatus::APPROVED) {
                    /** @var CustomerBookingEventTicket $bookingTicket */
                    foreach ($booking->getTicketsBooking()->getItems() as $bookingTicket) {
                        $reservedTicketsSpots += $bookingTicket->getPersons()->getValue();
                    }
                }
            }

            if ($newBooking) {
                /** @var CustomerBookingEventTicket $newBookingTicket */
                foreach ($newBooking->getTicketsBooking()->getItems() as $newBookingTicket) {
                    $reservedTicketsSpots += $newBookingTicket->getPersons() ? $newBookingTicket->getPersons()->getValue() : 0;
                }
            }

            $hasCapacity = ($newBooking ? $reservedTicketsSpots <= $availableTicketsSpots : $reservedTicketsSpots < $availableTicketsSpots);
        } else {
            $persons = 0;

            /** @var CustomerBooking $booking */
            foreach ($reservation->getBookings()->getItems() as $booking) {
                if ($booking->getStatus()->getValue() === BookingStatus::APPROVED) {
                    $persons += $booking->getPersons()->getValue();
                }
            }
            if ($newBooking) {
                $hasCapacity = ($reservation->getMaxCapacity()->getValue() - $persons - $newBooking->getPersons()->getValue()) >= 0;
            } else {
                $hasCapacity = $reservation->getMaxCapacity()->getValue() - $persons > 0;
            }
        }

        $bookingCloses = $reservation->getBookingCloses() ?
            $reservation->getBookingCloses()->getValue() :
            $reservation->getPeriods()->getItem(0)->getPeriodStart()->getValue();

        $bookingOpens = $reservation->getBookingOpens() ?
            $reservation->getBookingOpens()->getValue() :
            $reservation->getCreated()->getValue();

        $hasWaitingList = false;
        $eventSettings  = $reservation->getSettings() ? json_decode($reservation->getSettings()->getValue(), true) : null;

        /** @var SettingsService $settingsDS */
        $settingsDS          = $this->container->get('domain.settings.service');
        $waitingListSettings = $settingsDS->getSetting('appointments', 'waitingListEvents');

        if (
            $waitingListSettings['enabled'] && $eventSettings && !empty($eventSettings['waitingList']) &&
            $eventSettings['waitingList']['enabled']
        ) {
            $waitingCustomers = 0;

            foreach ($reservation->getBookings()->getItems() as $booking) {
                if ($booking->getStatus()->getValue() === BookingStatus::WAITING) {
                    if ($reservation->getCustomPricing()->getValue()) {
                        foreach ($booking->getTicketsBooking()->getItems() as $item) {
                            $waitingCustomers += $item->getPersons()->getValue();
                        }
                    } else {
                        $waitingCustomers += $booking->getPersons()->getValue();
                    }
                }
            }

            if ($newBooking && $reservation->getCustomPricing() && $reservation->getCustomPricing()->getValue()) {
                foreach ($newBooking->getTicketsBooking()->getItems() as $item) {
                    if (!$reservation->getMaxCustomCapacity()) {
                        $ticketData = $reservation->getCustomTickets()->getItem($item->getId()->getValue());

                        $hasWaitingList = $ticketData->getWaitingListSpots() && $ticketData->getWaitingListSpots()->getValue() >=
                            ($item->getPersons() ? $item->getPersons()->getValue() : 0
                                + ($ticketData->getWaiting() ? $ticketData->getWaiting()->getValue() : 0));
                    }
                }

                if ($reservation->getMaxCustomCapacity()) {
                    $hasWaitingList = $eventSettings['waitingList']['maxCapacity'] > $waitingCustomers;
                }
            } else {
                $hasWaitingList = $eventSettings['waitingList']['maxCapacity'] > $waitingCustomers;
            }

            $eventSettings['waitingList']['peopleWaiting'] = $waitingCustomers;

            $reservation->setSettings(new Json(json_encode($eventSettings)));
        }

        return $dateTime > $bookingOpens &&
            $dateTime < $bookingCloses &&
            ($hasCapacity || ($hasWaitingList && $newBooking && $newBooking->getStatus()->getValue() === BookingStatus::WAITING)) &&
            in_array($reservation->getStatus()->getValue(), [BookingStatus::APPROVED, BookingStatus::PENDING], true);
    }

    /**
     * @param CustomerBooking $booking
     * @param Event           $bookable
     * @param bool            $invoice
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getPaymentAmount($booking, $bookable, $invoice = false)
    {
        /** @var TaxApplicationService $taxApplicationService */
        $taxApplicationService = $this->container->get('application.tax.service');

        /** @var Tax $eventTax */
        $eventTax = $this->getTax($booking->getTax());

        $persons = $booking->getPersons()->getValue();

        $unitPrice = (float)$bookable->getPrice()->getValue();

        $price = $unitPrice * ($this->isAggregatedPrice($bookable) ? $persons : 1);

        $taxAmount = 0;

        $ticketsTax = [];

        if (
            $booking->getTicketsBooking() &&
            $booking->getTicketsBooking()->length() &&
            $bookable->getCustomPricing() &&
            $bookable->getCustomPricing()->getValue()
        ) {
            /** @var EventApplicationService $eventApplicationService */
            $eventApplicationService = $this->container->get('application.booking.event.service');

            $bookable->setCustomTickets(
                $eventApplicationService->getTicketsPriceByDateRange($bookable->getCustomTickets())
            );

            $ticketSumPrice = 0;

            /** @var CustomerBookingEventTicket $bookingToEventTicket */
            foreach ($booking->getTicketsBooking()->getItems() as $bookingToEventTicket) {
                /** @var EventTicket $ticket */
                $ticket = $bookable->getCustomTickets()->getItem($bookingToEventTicket->getEventTicketId()->getValue());

                $ticketPrice = $ticket->getDateRangePrice() ?
                    $ticket->getDateRangePrice()->getValue() : $ticket->getPrice()->getValue();

                $ticketPersons = $bookingToEventTicket->getPersons() ?
                    ($booking->getAggregatedPrice()->getValue() ? $bookingToEventTicket->getPersons()->getValue() : 1)
                    : 0;

                $ticketSubtotal = $ticketPersons * $ticketPrice;

                $ticketSumPrice += $ticketSubtotal;

                if ($bookingToEventTicket->getId()) {
                    if ($eventTax && $eventTax->getExcluded()->getValue()) {
                        $ticketsTax[$bookingToEventTicket->getId()->getValue()] = $this->getTaxAmount($eventTax, $ticketSubtotal);
                    } elseif ($eventTax && !$eventTax->getExcluded()->getValue()) {
                        $ticketsTax[$bookingToEventTicket->getId()->getValue()] =
                            $this->getTaxAmount($eventTax, $taxApplicationService->getBasePrice($ticketSubtotal, $eventTax));
                    } else {
                        $ticketsTax[$bookingToEventTicket->getId()->getValue()] = 0;
                    }
                }
            }

            $price = $ticketSumPrice;
        }

        if ($eventTax && !$eventTax->getExcluded()->getValue() && ($booking->getCoupon() || $invoice)) {
            $price = $taxApplicationService->getBasePrice($price, $eventTax);
        }

        $subtotalPrice = $price;

        $subtraction = 0;

        $reductionAmount = [
            'deduction' => 0,
            'discount'  => 0,
        ];

        if ($booking->getCoupon()) {
            $reductionAmount['discount'] = $price / 100 * ($booking->getCoupon()->getDiscount()->getValue() ?: 0);

            $reductionAmount['deduction'] = $booking->getCoupon()->getDeduction()->getValue();

            $subtraction = $reductionAmount['discount'] + $reductionAmount['deduction'];

            $price = max($price - $subtraction, 0);
        }

        if ($eventTax && ($eventTax->getExcluded()->getValue() || $subtraction || $invoice)) {
            $taxAmount = $this->getTaxAmount($eventTax, $price);
            $price    += $taxAmount;
        }

        $price = (float)max(round($price, 2), 0);

        if (!$price) {
            foreach ($ticketsTax as $ticketId => $ticketTax) {
                $ticketsTax[$ticketId] = 0;
            }
        }

        return [
            'price'        => apply_filters('amelia_modify_payment_amount', $price, $booking),
            'discount'     => $reductionAmount['discount'],
            'deduction'    => $reductionAmount['deduction'],
            'unit_price'   => $unitPrice,
            'qty'          => $this->isAggregatedPrice($bookable) ? $persons : 1,
            'subtotal'     => $subtotalPrice,
            'tax'          => $eventTax ? $this->getTaxAmount($eventTax, $subtotalPrice) : 0,
            'total_tax'    => $taxAmount,
            'tax_rate'     => $eventTax ? $this->getTaxRate($eventTax) : '',
            'tax_type'     => $eventTax ? $eventTax->getType()->getValue() : '',
            'tax_excluded' => $eventTax ? $eventTax->getExcluded()->getValue() : false,
            'tickets_tax'  => $ticketsTax,
            'full_discount' => $reductionAmount['discount'] + $reductionAmount['deduction']
        ];
    }

    /**
     * @param Reservation  $reservation
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function getReservationPaymentAmount($reservation)
    {
        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        /** @var Event $bookable */
        $bookable = $reservation->getBookable();

        $paymentAmount = $this->getPaymentAmount($reservation->getBooking(), $bookable)['price'];

        if ($reservation->getApplyDeposit()->getValue()) {
            $personsCount = $reservation->getBooking()->getPersons()->getValue();

            if ($reservation->getBooking()->getTicketsBooking() && $bookable->getCustomPricing()->getValue()) {
                $personsCount = 0;

                /** @var CustomerBookingEventTicket $bookingToEventTicket */
                foreach ($reservation->getBooking()->getTicketsBooking()->getItems() as $bookingToEventTicket) {
                    $personsCount += ($bookingToEventTicket->getPersons() ?
                        $bookingToEventTicket->getPersons()->getValue() : 0);
                }
            }

            $paymentAmount = $depositAS->calculateDepositAmount(
                $paymentAmount,
                $bookable,
                $personsCount
            );
        }

        return $paymentAmount;
    }

    /**
     * @param Reservation $reservation
     * @param bool        $usePayment
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getProvidersPaymentAmount($reservation, $usePayment = true)
    {
        $amountData = [];

        /** @var Payment $payment */
        $payment = $usePayment ? $reservation->getBooking()->getPayments()->getItem(0) : null;

        /** @var Event $event */
        $event = $reservation->getBookable();

        /** @var Provider $provider */
        foreach ($event->getProviders()->getItems() as $provider) {
            $amountData[$provider->getId()->getValue()][] = [
                'paymentId' => $payment ? $payment->getId()->getValue() : null,
                'amount'    => $this->getReservationPaymentAmount($reservation),
            ];
        }

        return $amountData;
    }

    /**
     * @param Payment $payment
     * @param boolean $fromLink
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getReservationByPayment($payment, $fromLink = false)
    {
        $result = new CommandResult();

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::EVENT);

        /** @var Event $event */
        $event = $reservationService->getReservationByBookingId(
            $payment->getCustomerBookingId()->getValue()
        );

        if ($event->getLocationId()) {
            /** @var Location $location */
            $location = $locationRepository->getById($event->getLocationId()->getValue());

            $event->setLocation($location);
        }

        /** @var CustomerBooking $booking */
        $booking = $event->getBookings()->getItem($payment->getCustomerBookingId()->getValue());

        $booking->setChangedStatus(new BooleanValueObject(true));

        $this->setToken($booking);

        /** @var AbstractUser $customer */
        $customer = $customerRepository->getById($booking->getCustomerId()->getValue());

        $result->setData(
            [
                'type'                     => Entities::EVENT,
                Entities::EVENT            => $event->toArray(),
                Entities::BOOKING          => $booking->toArray(),
                'appointmentStatusChanged' => false,
                'customer'                 => $customer->toArray(),
                'packageId'                => 0,
                'recurring'                => [],
                'utcTime'                  => $reservationService->getBookingPeriods(
                    $event,
                    $booking,
                    $event
                ),
                'isRetry'                  => !$fromLink,
                'fromLink'                 => $fromLink,
                'paymentId'                => $payment->getId()->getValue(),
                'packageCustomerId'        => null,
                'payment'                  => [
                    'id'            => $payment->getId()->getValue(),
                    'amount'        => $payment->getAmount()->getValue(),
                    'gateway'       => $payment->getGateway()->getName()->getValue(),
                    'gatewayTitle'  => $payment->getGatewayTitle() ? $payment->getGatewayTitle()->getValue() : '',
                    'invoiceNumber' => $payment->getInvoiceNumber() ? $payment->getInvoiceNumber()->getValue() : '',
                ],
            ]
        );

        return $result;
    }

    /**
     * @param int $bookingId
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getBookingResultByBookingId($bookingId)
    {
        $result = new CommandResult();

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::EVENT);

        /** @var Event $event */
        $event = $reservationService->getReservationByBookingId(
            $bookingId
        );

        if ($event->getLocationId()) {
            /** @var Location $location */
            $location = $locationRepository->getById($event->getLocationId()->getValue());

            $event->setLocation($location);
        }

        /** @var CustomerBooking $booking */
        $booking = $event->getBookings()->getItem($bookingId);

        $booking->setChangedStatus(new BooleanValueObject(true));

        $this->setToken($booking);

        /** @var AbstractUser $customer */
        $customer = $customerRepository->getById($booking->getCustomerId()->getValue());

        $result->setData(
            [
                'type'                     => Entities::EVENT,
                Entities::EVENT            => $event->toArray(),
                Entities::BOOKING          => $booking->toArray(),
                'appointmentStatusChanged' => false,
                'customer'                 => $customer->toArray(),
                'packageId'                => 0,
                'recurring'                => [],
                'utcTime'                  => $reservationService->getBookingPeriods(
                    $event,
                    $booking,
                    $event
                ),
                'isRetry'                  => true,
                'paymentId'                => null,
                'packageCustomerId'        => null,
                'payment'                  => null,
            ]
        );

        return $result;
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws QueryExecutionException
     */
    public function manageTaxes(&$data)
    {
        /** @var TaxApplicationService $taxAS */
        $taxAS = $this->container->get('application.tax.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $taxesSettings = $settingsService->getSetting('payments', 'taxes');

        if ($taxesSettings['enabled']) {
            /** @var Collection $taxes */
            $taxes = $taxAS->getAll();

            $data['bookings'][0]['tax'] = $taxAS->getTaxData(
                $data['eventId'],
                Entities::EVENT,
                $taxes
            );
        }
    }

    /**
     * @param int $bookingId
     * @param string $token
     *
     * @return array
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function deleteBooking($bookingId, $token = null)
    {
        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');

        /** @var CustomerBooking $customerBooking */
        $customerBooking = $customerBookingRepository->getById((int)$bookingId);

        if (!$customerBooking) {
            throw new \Exception('Booking not found');
        }

        if (!$customerBooking->getToken() || $customerBooking->getToken()->getValue() !== $token) {
            throw new AccessDeniedException('Invalid token');
        }

        $customerBookingRepository->beginTransaction();

        if (!$eventApplicationService->deleteEventBooking($customerBooking)) {
            $customerBookingRepository->rollback();
            throw new \Exception('Could not delete booking');
        }

        $customerBookingRepository->commit();

        return [];
    }
}
