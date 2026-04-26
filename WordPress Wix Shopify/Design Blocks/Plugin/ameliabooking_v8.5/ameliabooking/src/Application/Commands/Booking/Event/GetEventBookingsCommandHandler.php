<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventTicket;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use Exception;

/**
 * Class GetEventBookingsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventBookingsCommandHandler extends CommandHandler
{
    /**
     * @param GetEventBookingsCommand $command
     *
     * @return CommandResult
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function handle(GetEventBookingsCommand $command)
    {
        $result = new CommandResult();

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');
        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        $params = $command->getField('params');

        try {
            /** @var AbstractUser $user */
            $user = $command->getUserApplicationService()->authorization(
                $command->getToken(),
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

        if ($user && $userAS->isAmeliaUser($user) && $userAS->isCustomer($user)) {
            $params['customers'] = [$user->getId()->getValue()];
        }

        if ($user && $user->getType() === AbstractUser::USER_ROLE_PROVIDER) {
            $params['providers'] = [$user->getId()->getValue()];
        }

        $providerTimeZoneSet = ($user instanceof Provider) && $user->getTimeZone() && $user->getTimeZone()->getValue();

        if (isset($params['dates'][0])) {
            $params['dates'][0] ? $params['dates'][0] .= ' 00:00:00' : null;
        }

        if (isset($params['dates'][1])) {
            $params['dates'][1] ? $params['dates'][1] .= ' 23:59:59' : null;
        }

        $itemsPerPageBackEnd = $settingsDS->getSetting('general', 'itemsPerPageBackEnd');

        /** @var Event $event */
        $event = $eventApplicationService->getEventById(
            (int)$params['events'][0],
            [
                'fetchEventsTickets'    => true,
                'fetchBookings'         => true,
                'fetchBookingsTickets'  => true,
            ]
        );

        $attendeeCount = 0;

        $waitingCount = 0;

        $maxCapacity = 0;

        if ($event->getCustomPricing()->getValue()) {
            /** @var CustomerBooking $customerBooking */
            foreach ($event->getBookings()->getItems() as $customerBooking) {
                if (
                    $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $customerBooking->getStatus()->getValue() === BookingStatus::PENDING
                ) {
                    /** @var CustomerBookingEventTicket $bookingToEventTicket */
                    foreach ($customerBooking->getTicketsBooking()->getItems() as $bookingToEventTicket) {
                        $attendeeCount += $bookingToEventTicket->getPersons()->getValue();
                    }
                }

                if ($customerBooking->getStatus()->getValue() === BookingStatus::WAITING) {
                    /** @var CustomerBookingEventTicket $bookingToEventTicket */
                    foreach ($customerBooking->getTicketsBooking()->getItems() as $bookingToEventTicket) {
                        $waitingCount += $bookingToEventTicket->getPersons()->getValue();
                    }
                }
            }

            /** @var EventTicket $ticket */
            foreach ($event->getCustomTickets()->getItems() as $ticket) {
                $maxCapacity += $ticket->getSpots()->getValue();
            }
        } else {
            $maxCapacity = $event->getMaxCapacity()->getValue();

            /** @var CustomerBooking $customerBooking */
            foreach ($event->getBookings()->getItems() as $customerBooking) {
                if (
                    $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $customerBooking->getStatus()->getValue() === BookingStatus::PENDING
                ) {
                    $attendeeCount += $customerBooking->getPersons()->getValue();
                }
            }

            /** @var CustomerBooking $customerBooking */
            foreach ($event->getBookings()->getItems() as $customerBooking) {
                if ($customerBooking->getStatus()->getValue() === BookingStatus::WAITING) {
                    $waitingCount += $customerBooking->getPersons()->getValue();
                }
            }
        }

        $waitingListSettings = $settingsDS->getSetting('appointments', 'waitingListEvents');

        $eventSettings = $waitingListSettings['enabled'] && $event->getSettings() && $event->getSettings()->getValue()
            ? json_decode($event->getSettings()->getValue(), true)
            : null;

        $waitingCapacity = 0;

        if ($eventSettings && !empty($eventSettings['waitingList']['enabled'])) {
            if ($event->getCustomPricing()->getValue()) {
                /** @var EventTicket $ticket */
                foreach ($event->getCustomTickets()->getItems() as $ticket) {
                    $waitingCapacity += $ticket->getWaitingListSpots()->getValue();
                }
            } else {
                $waitingCapacity = $eventSettings['waitingList']['maxCapacity'];
            }
        }

        $bookingIds = $bookingRepository->getEventBookingIdsByCriteria($params, $itemsPerPageBackEnd);

        if (!$bookingIds && $params['page'] && (int)$params['page'] > 1) {
            $params['page'] = 1;

            $bookingIds = $bookingRepository->getEventBookingIdsByCriteria($params, $itemsPerPageBackEnd);
        }

        if (empty($bookingIds)) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully retrieved event bookings');
            $result->setData(
                [
                    Entities::BOOKINGS => [],
                    'totalCount'       => sizeof($bookingRepository->getEventBookingIdsByCriteria()),
                    'filteredCount'    => 0,
                    'attendeeCount'    => 0,
                    'waitingCount'     => 0,
                    'waitingCapacity'  => $waitingCapacity,
                    'maxCapacity'      => $maxCapacity,
                ]
            );

            return $result;
        }

        $bookings = $bookingRepository->getEventBookingsByIds(
            $bookingIds,
            array_merge(
                !empty($params['dates']) ? ['dates' => $params['dates']] : [],
                [
                    'fetchBookingsPayments' => true,
                    'fetchBookingsCoupons' => true,
                    'fetchProviders' => true,
                    'fetchCustomers' => true
                ]
            )
        );


        $customersNoShowCountIds = [];

        $noShowTagEnabled = $settingsDS->getSetting('roles', 'enableNoShowTag');

        $eventBookings = [];

        foreach ($bookings as $key => &$booking) {
            ksort($booking['payments']);

            if ($noShowTagEnabled) {
                $customersNoShowCountIds[] = $booking['customer']['id'];
            }

            foreach ($booking['eventPeriods'] as &$period) {
                $period['periodStart'] = DateTimeService::getCustomDateTimeFromUtc($period['periodStart']);
                if ($providerTimeZoneSet) {
                    $period['periodStart'] =
                        DateTimeService::getCustomDateTimeObjectInTimeZone($period['periodStart'], $user->getTimeZone()->getValue())->format('Y-m-d H:i:s');
                }
            }

            $persons = $booking['persons'];
            if (!empty($booking['event']['customPricing']) && !empty($booking['ticketsData'])) {
                /** @var CustomerBookingEventTicket $bookedTicket */
                foreach ($booking['ticketsData'] as $bookedTicket) {
                    $persons += $bookedTicket['persons'];
                }
            }

            if ($booking['tax']) {
                $booking['tax'] = json_decode($booking['tax'], true);
            }

            $booking['ticketsData'] = !empty($booking['ticketsData']) ? $booking['ticketsData'] : [];

            $eventBookings[] = [
                'id' => $booking['id'],
                'bookedSpots' => $persons,
                'status' => $booking['event']['status'] === 'canceled' || $booking['event']['status'] === 'rejected' ? 'canceled' : $booking['status'],
                'checked' => false,
                'customer' => [
                    'id' => $booking['customer']['id'],
                    'firstName' => $booking['customer']['firstName'],
                    'lastName' => $booking['customer']['lastName'],
                    'phone' => $booking['customer']['phone'],
                    'email' => $booking['customer']['email'],
                    'note' => $booking['customer']['note']
                ],
                'code' => !empty($booking['token']) ? substr($booking['token'], 0, 5) : '',
                'event' => [
                    'id' => $booking['event']['id'],
                    'name' => $booking['event']['name'],
                    'startDate' => explode(' ', array_values($booking['eventPeriods'])[0]['periodStart'])[0],
                    'startTime' => explode(' ', array_values($booking['eventPeriods'])[0]['periodStart'])[1],
                    'organizer' => !empty($booking['event']['organizer']) ? $booking['event']['organizer'] : null,
                    'staff' => !empty($booking['event']['providers']) ? array_values($booking['event']['providers']) : [],
                    'isZoom' => !empty(array_values($booking['eventPeriods'])[0]['zoomMeeting']),
                    'isGoogleMeet' => !empty(array_values($booking['eventPeriods'])[0]['googleMeetUrl']),
                    'isWaitingList' => $booking['event']['isWaitingList'],
                ],
                'persons' => $booking['persons'],
                'customFields' => $booking['customFields'],
                'ticketsData' => $booking['ticketsData'],
                'tax' => $booking['tax'],
                'price' => $booking['price'],
                'aggregatedPrice' => $booking['aggregatedPrice'],
                'coupon' => !empty($booking['coupon']) ? $booking['coupon'] : null,
                'payment' => [
                    'status' => $paymentAS->getFullStatus($booking, BookableType::EVENT),
                    'total'  => $paymentAS->calculateAppointmentPrice($booking, BookableType::EVENT),
                ],
                'payments' => array_values($booking['payments']),
            ];
        }


        if ($noShowTagEnabled && !empty($customersNoShowCountIds)) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            $customersNoShowCount = $bookingRepository->countByNoShowStatus($customersNoShowCountIds);

            foreach ($eventBookings as &$eventBooking) {
                if (!empty($customersNoShowCount[$eventBooking['customer']['id']])) {
                    $eventBooking['customer']['noShowCount'] = $customersNoShowCount[$eventBooking['customer']['id']]['count'];
                }
            }
        }

        $eventBookings = apply_filters('amelia_get_event_bookings_filter', $eventBookings);

        do_action('amelia_get_event_bookings', $eventBookings);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved event bookings');
        $result->setData(
            [
                Entities::BOOKINGS => $eventBookings,
                'totalCount'       => sizeof($bookingRepository->getEventBookingIdsByCriteria()),
                'filteredCount'    => sizeof(
                    $bookingRepository->getEventBookingIdsByCriteria($params)
                ),
                'attendeeCount'    => $attendeeCount,
                'waitingCount'     => $waitingCount,
                'waitingCapacity'  => $waitingCapacity,
                'maxCapacity'      => $maxCapacity,
            ]
        );

        return $result;
    }
}
