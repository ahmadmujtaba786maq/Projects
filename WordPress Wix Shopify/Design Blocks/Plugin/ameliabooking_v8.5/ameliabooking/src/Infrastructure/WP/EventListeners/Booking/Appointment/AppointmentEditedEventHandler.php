<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Booking\IcsApplicationService;
use AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService;
use AmeliaBooking\Application\Services\Notification\ApplicationNotificationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AppointmentEditedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class AppointmentEditedEventHandler
{
    /** @var string */
    public const APPOINTMENT_EDITED = 'appointmentEdited';
    /** @var string */
    public const APPOINTMENT_STATUS_AND_TIME_UPDATED = 'appointmentStatusAndTimeUpdated';
    /** @var string */
    public const TIME_UPDATED = 'bookingTimeUpdated';
    /** @var string */
    public const BOOKING_STATUS_UPDATED = 'bookingStatusUpdated';
    /** @var string */
    public const ZOOM_USER_CHANGED = 'zoomUserChanged';
    /** @var string */
    public const ZOOM_LICENCED_USER_CHANGED = 'zoomLicencedUserChanged';
    /** @var string */
    public const APPOINTMENT_STATUS_AND_ZOOM_LICENCED_USER_CHANGED = 'appointmentStatusAndZoomLicencedUserChanged';
    /** @var string */
    public const BOOKING_ADDED = 'bookingAdded';

    /**
     * @param CommandResult $commandResult
     * @param Container     $container
     *
     * @throws ContainerValueNotFoundException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function handle($commandResult, $container)
    {
        /** @var ApplicationNotificationService $applicationNotificationService */
        $applicationNotificationService = $container->get('application.notification.service');
        /** @var ApplicationIntegrationService $applicationIntegrationService */
        $applicationIntegrationService = $container->get('application.integration.service');
        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');
        /** @var AbstractWebHookApplicationService $webHookService */
        $webHookService = $container->get('application.webHook.service');
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $container->get('application.booking.booking.service');
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $container->get('application.payment.service');
        /** @var IcsApplicationService $icsService */
        $icsService = $container->get('application.ics.service');

        $appointment = $commandResult->getData()[Entities::APPOINTMENT];

        $bookings = $commandResult->getData()['bookingsWithChangedStatus'];

        $oldAppointmentStatus = $commandResult->getData()['oldAppointmentStatus'];

        $appointmentStatusChanged = $commandResult->getData()['appointmentStatusChanged'];

        $appointmentRescheduled = $commandResult->getData()['appointmentRescheduled'];

        $appointmentEmployeeChanged = $commandResult->getData()['appointmentEmployeeChanged'];

        $appointmentZoomUserChanged = $commandResult->getData()['appointmentZoomUserChanged'];

        $bookingAdded = !empty($commandResult->getData()['bookingAdded']) ? $commandResult->getData()['bookingAdded'] : null;

        $appointmentZoomUsersLicenced = $commandResult->getData()['appointmentZoomUsersLicenced'];

        /** @var Appointment $reservationObject */
        $reservationObject = AppointmentFactory::create($appointment);

        $bookingApplicationService->setReservationEntities($reservationObject);

        /** @var Collection $removedBookings */
        $removedBookings = new Collection();

        foreach ($bookings as $booking) {
            if ($booking['isChangedStatus'] && $booking['status'] === BookingStatus::REJECTED) {
                $removedBookings->addItem(CustomerBookingFactory::create($booking));
            }
        }

        /** @var CustomerBooking $customerBooking */
        foreach ($reservationObject->getBookings()->getItems() as $index => $customerBooking) {
            if (
                (($customerBooking->isNew() && $customerBooking->isNew()->getValue()) || $appointmentRescheduled) && (
                    $customerBooking->getStatus()->getValue() === BookingStatus::PENDING ||
                    $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED
                ) && (
                    $reservationObject->getStatus()->getValue() === BookingStatus::PENDING ||
                    $reservationObject->getStatus()->getValue() === BookingStatus::APPROVED
                )
            ) {
                $customerBooking->setIcsFiles(
                    $icsService->getIcsData(Entities::APPOINTMENT, $customerBooking->getId()->getValue(), [], true)
                );
            }

            if (!empty($appointment['createPaymentLinks'])) {
                /** @var Payment $payment */
                $payment = $customerBooking->getPayments()->length()
                    ? $customerBooking->getPayments()->getItem(0)
                    : null;

                if ($payment) {
                    $paymentLinks = $paymentAS->createPaymentLink(
                        [
                            'booking'     => $customerBooking->toArray(),
                            'type'        => Entities::APPOINTMENT,
                            'appointment' => $reservationObject->toArray(),
                            'paymentId'   => $payment->getId()->getValue(),
                            'bookable'    => $reservationObject->getService()->toArray(),
                            'customer'    => $customerBooking->getCustomer()->toArray(),
                        ],
                        $index
                    );

                    if ($paymentLinks) {
                        $payment->setPaymentLinks($paymentLinks);
                    }
                }
            }
        }

        $commandSlug = self::APPOINTMENT_EDITED;

        if ($appointmentEmployeeChanged && $appointmentZoomUserChanged && $appointmentZoomUsersLicenced && $appointmentStatusChanged) {
            $commandSlug = self::APPOINTMENT_STATUS_AND_ZOOM_LICENCED_USER_CHANGED;
        } elseif ($appointmentEmployeeChanged && $appointmentZoomUserChanged && $appointmentZoomUsersLicenced) {
            $commandSlug = self::ZOOM_LICENCED_USER_CHANGED;
        } elseif ($appointmentEmployeeChanged && $appointmentZoomUserChanged) {
            $commandSlug = self::ZOOM_USER_CHANGED;
        } elseif ($appointmentStatusChanged && $appointmentRescheduled) {
            $commandSlug = self::APPOINTMENT_STATUS_AND_TIME_UPDATED;
        } elseif ($appointmentStatusChanged) {
            $commandSlug = self::BOOKING_STATUS_UPDATED;
        } elseif ($appointmentRescheduled) {
            $commandSlug = self::TIME_UPDATED;
        }

        $applicationIntegrationService->handleAppointment(
            $reservationObject,
            $appointment,
            $commandSlug,
            [
                ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => true,
                ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => true,
                ApplicationIntegrationService::SKIP_APPLE_CALENDAR => true
            ]
        );

        $applicationIntegrationService->handleAppointmentEmployeeChange(
            $reservationObject,
            $appointment,
            $appointmentEmployeeChanged
        );

        if ($appointmentStatusChanged || $appointmentEmployeeChanged) {
            $applicationNotificationService->sendAppointmentProviderStatusNotifications(
                $reservationObject
            );
        }

        $applicationNotificationService->sendAppointmentCustomersStatusNotifications(
            $reservationObject,
            $reservationObject->getBookings(),
            true,
            true,
            !empty($settingsService->getSetting('notifications', 'sendInvoice')),
            $reservationObject->isNotifyParticipants()
        );

        $applicationNotificationService->sendAppointmentCustomersStatusNotifications(
            $reservationObject,
            $removedBookings,
            true,
            true,
            false,
            $reservationObject->isNotifyParticipants()
        );

        if (
            $appointmentRescheduled &&
            (
                $reservationObject->getStatus()->getValue() === BookingStatus::APPROVED ||
                $reservationObject->getStatus()->getValue() === BookingStatus::PENDING
            )
        ) {
            $applicationNotificationService->sendAppointmentRescheduleNotifications(
                $reservationObject,
                !$appointmentEmployeeChanged
            );
        }

        if (
            $appointmentEmployeeChanged &&
            (
                $reservationObject->getStatus()->getValue() === BookingStatus::APPROVED ||
                $oldAppointmentStatus === BookingStatus::APPROVED
            )
        ) {
            $applicationNotificationService->sendAppointmentUpdatedNotifications(
                $reservationObject,
                $appointmentEmployeeChanged,
                $oldAppointmentStatus === BookingStatus::APPROVED,
                !$appointmentStatusChanged && !$appointmentRescheduled
            );
        }

        if (
            !$appointmentStatusChanged &&
            !$appointmentRescheduled &&
            $reservationObject->getStatus()->getValue() === BookingStatus::APPROVED
        ) {
            $applicationNotificationService->sendAppointmentUpdatedNotifications(
                $reservationObject,
                null,
                !$appointmentEmployeeChanged,
                !$appointmentEmployeeChanged
            );
        }

        if ($appointmentRescheduled === true) {
            $webHookService->process(self::TIME_UPDATED, $appointment, []);
        }

        if ($bookings) {
            $canceledBookings = [];
            $otherBookings    = [];
            foreach ($bookings as $booking) {
                if ($booking['status'] === BookingStatus::CANCELED) {
                    $canceledBookings[] = $booking;
                } else {
                    $otherBookings[] = $booking;
                }
            }

            if (count($canceledBookings) > 0) {
                $webHookService->process(BookingCanceledEventHandler::BOOKING_CANCELED, $appointment, $canceledBookings);
            }
            if (count($otherBookings) > 0) {
                $webHookService->process(($bookingAdded ? self::BOOKING_ADDED : self::BOOKING_STATUS_UPDATED), $appointment, $otherBookings);
            }
        }
    }
}
