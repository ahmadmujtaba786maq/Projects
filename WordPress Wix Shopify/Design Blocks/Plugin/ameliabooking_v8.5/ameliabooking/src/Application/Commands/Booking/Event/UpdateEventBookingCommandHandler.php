<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventTicket;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\Booking\Event\CustomerBookingEventTicketFactory;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventTicketRepository;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateEventBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class UpdateEventBookingCommandHandler extends CommandHandler
{
    /**
     * @param UpdateEventBookingCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateEventBookingCommand $command)
    {
        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var AbstractUser $user */
        $user = $this->container->get('logged.in.user');

        if (!$command->getPermissionService()->currentUserCanWrite(Entities::EVENTS)) {
            $user = $userAS->getAuthenticatedUser($command->getToken(), false, 'providerCabinet');

            if ($user === null) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not retrieve user');
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );

                return $result;
            }
        }

        $this->checkMandatoryFields($command);

        $bookingData = $command->getField('bookings') ? $command->getField('bookings')[0] : null;

        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var EventApplicationService $eventAS */
        $eventAS = $this->container->get('application.booking.event.service');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::EVENT);

        /** @var Event $event */
        $event = $reservationService->getReservationByBookingId((int)$command->getField('id'));

        /** @var CustomerBooking $customerBooking */
        $customerBooking = $event->getBookings()->getItem((int)$command->getField('id'));

        do_action('amelia_before_event_booking_updated', $customerBooking ? $customerBooking->toArray() : null, $bookingData);

        if (
            $user &&
            $userAS->isProvider($user) &&
            (
                !$settingsDS->getSetting('roles', 'allowWriteEvents') ||
                (!$event->getProviders()->keyExists($user->getId()->getValue()) &&
                (!$event->getOrganizerId() || $event->getOrganizerId()->getValue() !== $user->getId()->getValue()))
            )
        ) {
            throw new AccessDeniedException('You are not allowed to update booking');
        }

        $isBookingStatusChanged =
            $bookingData &&
            isset($bookingData['status']) &&
            $customerBooking->getStatus()->getValue() !== $bookingData['status'];

        if (isset($bookingData['customFields'])) {
            $customerBooking->setCustomFields(new Json(json_encode($bookingData['customFields'])));
        }

        if (isset($bookingData['status'])) {
            $customerBooking->setStatus(new BookingStatus($bookingData['status']));
        }

        if (isset($bookingData['coupon'])) {
            $customerBooking->setCoupon(CouponFactory::create($bookingData['coupon']));
        }


        /** @var CustomerBookingEventTicketRepository $bookingEventTicketRepository */
        $bookingEventTicketRepository =
            $this->container->get('domain.booking.customerBookingEventTicket.repository');

        if (
            $event->getCustomPricing() &&
            $event->getCustomPricing()->getValue() &&
            $event->getCustomTickets() &&
            $event->getCustomTickets()->length()
        ) {
            $event->setCustomTickets($eventAS->getTicketsPriceByDateRange($event->getCustomTickets()));

            if (!empty($bookingData['ticketsData'])) {
                foreach ($bookingData['ticketsData'] as $ticketBooking) {
                    if (empty($ticketBooking['id']) && $ticketBooking['persons']) {
                        /** @var EventTicket $ticket */
                        $ticket = $event->getCustomTickets()->getItem($ticketBooking['eventTicketId']);

                        $ticketPrice = $ticket->getDateRangePrice() ?
                            $ticket->getDateRangePrice()->getValue() : $ticket->getPrice()->getValue();

                        /** @var CustomerBookingEventTicket $bookingEventTicket */
                        $bookingEventTicket = CustomerBookingEventTicketFactory::create(
                            [
                                'eventTicketId'     => $ticketBooking['eventTicketId'],
                                'customerBookingId' => $customerBooking->getId()->getValue(),
                                'persons'           => $ticketBooking['persons'],
                                'price'             => $ticketPrice,
                            ]
                        );

                        $newTicketBookingId = $bookingEventTicketRepository->add($bookingEventTicket);
                        if ($customerBooking->getStatus()->getValue() === BookingStatus::APPROVED) {
                            $isBookingStatusChanged = true;
                            $bookingEventTicket->setId(new Id($newTicketBookingId));
                            $customerBooking->getTicketsBooking()->addItem($bookingEventTicket);
                        }
                    } elseif ($ticketBooking['id'] && $ticketBooking['persons']) {
                        $bookingEventTicketRepository->update($ticketBooking['id'], $ticketBooking);

                        foreach ($customerBooking->getTicketsBooking()->getItems() as $item) {
                            if (
                                $item->getEventTicketId()->getValue() === $ticketBooking['eventTicketId'] &&
                                $item->getPersons()->getValue() < $ticketBooking['persons'] &&
                                $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED
                            ) {
                                $isBookingStatusChanged = true;
                                $item->setPersons(new IntegerValue($ticketBooking['persons']));
                            }
                        }
                    } elseif ($ticketBooking['id'] && !$ticketBooking['persons']) {
                        $bookingEventTicketRepository->delete($ticketBooking['id']);

                        foreach ($customerBooking->getTicketsBooking()->getItems() as $key => $item) {
                            if ($item->getId()->getValue() === $ticketBooking['id']) {
                                $customerBooking->getTicketsBooking()->deleteItem($key);
                                break;
                            }
                        }

                        if ($customerBooking->getStatus()->getValue() === BookingStatus::APPROVED) {
                            $isBookingStatusChanged = true;
                        }
                    }
                }
            }
        } elseif (!empty($bookingData['persons'])) {
            if (
                $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED &&
                $customerBooking->getPersons()->getValue() < $bookingData['persons']
            ) {
                $isBookingStatusChanged = true;
                $customerBooking->setPersons(new IntegerValue($bookingData['persons']));
            } elseif (
                $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED &&
                $customerBooking->getPersons()->getValue() > $bookingData['persons']
            ) {
                $customerBooking->setPersons(new IntegerValue($bookingData['persons']));
            }
        }

        if (
            $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED &&
            $customerBooking->getCustomFields() &&
            !empty($bookingData['customFields']) &&
            $customerBooking->getCustomFields()->getValue() !== json_encode($bookingData['customFields'])
        ) {
            $isBookingStatusChanged = true;
        }



        $customerBookingRepository->update($customerBooking->getId()->getValue(), $customerBooking);

        /** @var Payment $payment */
        foreach ($customerBooking->getPayments()->getItems() as $payment) {
            if (
                $payment->getWcOrderId() &&
                $payment->getWcOrderId()->getValue()
            ) {
                $eventArray = $event->toArray();

                $eventArray['bookings'] = [$customerBooking->toArray()];

                $eventArray['recurring'] = [];

                $eventArray['eventId'] = $eventArray['id'];

                $dateTimeValues = [];

                /** @var EventPeriod $period */
                foreach ($event->getPeriods()->getItems() as $period) {
                    $dateTimeValues[] = [
                        'start' => $period->getPeriodStart()->getValue()->format('Y-m-d H:i'),
                        'end'   => $period->getPeriodEnd()->getValue()->format('Y-m-d H:i')
                    ];
                }

                $eventArray['dateTimeValues'] = $dateTimeValues;

                foreach ($eventArray['bookings'] as &$booking) {
                    if (!empty($booking['customFields'])) {
                        $customFields = json_decode($booking['customFields'], true);

                        $booking['customFields'] = $customFields;
                    }
                }

                if (WooCommerceService::isEnabled()) {
                    WooCommerceService::updateItemMetaData(
                        $payment->getWcOrderId()->getValue(),
                        $payment->getWcOrderItemId() ? $payment->getWcOrderItemId()->getValue() : null,
                        $eventArray
                    );
                }
            }
        }

        do_action('amelia_after_event_booking_updated', $customerBooking->toArray(), $bookingData);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated booking');
        $result->setData(
            [
                'type'                     => Entities::EVENT,
                Entities::EVENT            => $event->toArray(),
                Entities::BOOKING          => $customerBooking->toArray(),
                'appointmentStatusChanged' => false,
                'bookingStatusChanged'     => $isBookingStatusChanged,
                'paymentId'                =>
                    $customerBooking->getPayments()->length() > 0 ?
                        $customerBooking->getPayments()->getItem($customerBooking->getPayments()->keys()[0])->getId()->getValue() :
                        null,
                'createPaymentLinks'       => $command->getField('createPaymentLinks')
            ]
        );

        return $result;
    }
}
