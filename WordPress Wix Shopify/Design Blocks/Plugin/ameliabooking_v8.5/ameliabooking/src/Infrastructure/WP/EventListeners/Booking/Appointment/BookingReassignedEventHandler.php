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
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BookingReassignedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class BookingReassignedEventHandler
{
    /** @var string */
    public const TIME_UPDATED = 'bookingTimeUpdated';

    /** @var string */
    public const ZOOM_USER_CHANGED = 'zoomUserChanged';

    /** @var string */
    public const ZOOM_LICENCED_USER_CHANGED = 'zoomLicencedUserChanged';

    /** @var string */
    public const APPOINTMENT_STATUS_AND_ZOOM_LICENCED_USER_CHANGED = 'appointmentStatusAndZoomLicencedUserChanged';

    /** @var string */
    public const APPOINTMENT_STATUS_AND_TIME_UPDATED = 'appointmentStatusAndTimeUpdated';

    /** @var string */
    public const BOOKING_STATUS_UPDATED = 'bookingStatusUpdated';

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

        /** @var AbstractWebHookApplicationService $webHookService */
        $webHookService = $container->get('application.webHook.service');

        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $container->get('application.booking.booking.service');

        /** @var IcsApplicationService $icsService */
        $icsService = $container->get('application.ics.service');


        $oldAppointmentStatus = $commandResult->getData()['oldAppointmentStatus'];

        $oldAppointmentStatusChanged = $commandResult->getData()['oldAppointmentStatusChanged'];

        $bookingEmployeeChanged = $commandResult->getData()['bookingEmployeeChanged'];

        $bookingRescheduled = $commandResult->getData()['bookingRescheduled'];

        $bookingZoomUserChanged = $commandResult->getData()['bookingZoomUserChanged'];

        $bookingZoomUsersLicenced = $commandResult->getData()['bookingZoomUsersLicenced'];

        $existingAppointmentStatusChanged = $commandResult->getData()['existingAppointmentStatusChanged'];


        /** @var CustomerBooking $booking */
        $booking = CustomerBookingFactory::create($commandResult->getData()['booking']);

        /** @var Collection $appointments */
        $appointments = new Collection();

        /** @var Appointment $oldAppointment */
        $oldAppointment = AppointmentFactory::create($commandResult->getData()['oldAppointment']);

        $oldAppointmentArray = [];

        $bookingApplicationService->setAppointmentEntities($oldAppointment, $appointments);

        $appointments->addItem($oldAppointment, $oldAppointment->getId()->getValue(), true);


        // appointment have single booking
        if (
            $commandResult->getData()['existingAppointment'] === null &&
            $commandResult->getData()['newAppointment'] === null
        ) {
            $commandSlug = ApplicationIntegrationService::APPOINTMENT_EDITED;

            if ($bookingZoomUserChanged && $bookingZoomUsersLicenced && $oldAppointmentStatusChanged) {
                $commandSlug = self::APPOINTMENT_STATUS_AND_ZOOM_LICENCED_USER_CHANGED;
            } elseif ($bookingZoomUserChanged && $bookingZoomUsersLicenced) {
                $commandSlug = self::ZOOM_LICENCED_USER_CHANGED;
            } elseif ($bookingZoomUserChanged) {
                $commandSlug = self::ZOOM_USER_CHANGED;
            } elseif ($oldAppointmentStatusChanged && $bookingRescheduled) {
                $commandSlug = self::APPOINTMENT_STATUS_AND_TIME_UPDATED;
            } elseif ($oldAppointmentStatusChanged) {
                $commandSlug = self::BOOKING_STATUS_UPDATED;
            } elseif ($bookingRescheduled) {
                $commandSlug = self::TIME_UPDATED;
            }

            $applicationIntegrationService->handleAppointment(
                $oldAppointment,
                $oldAppointmentArray,
                $commandSlug,
                [
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );

            if ($oldAppointmentStatusChanged || $bookingEmployeeChanged) {
                $applicationNotificationService->sendAppointmentProviderStatusNotifications(
                    $oldAppointment
                );
            }

            $applicationNotificationService->sendAppointmentCustomersStatusNotifications(
                $oldAppointment,
                $oldAppointment->getBookings(),
                true,
                true,
                false
            );

            if (
                $bookingRescheduled &&
                (
                    $oldAppointment->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $oldAppointment->getStatus()->getValue() === BookingStatus::PENDING
                )
            ) {
                /** @var CustomerBooking $customerBooking */
                foreach ($oldAppointment->getBookings()->getItems() as $customerBooking) {
                    $customerBooking->setIcsFiles(
                        $icsService->getIcsData(Entities::APPOINTMENT, $customerBooking->getId()->getValue(), [], true)
                    );
                }

                $applicationNotificationService->sendAppointmentRescheduleNotifications(
                    $oldAppointment,
                    !$bookingEmployeeChanged
                );
            }

            if (
                $bookingEmployeeChanged &&
                (
                    $oldAppointment->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $oldAppointmentStatus === BookingStatus::APPROVED
                )
            ) {
                $applicationNotificationService->sendAppointmentUpdatedNotifications(
                    $oldAppointment,
                    $bookingEmployeeChanged,
                    $oldAppointmentStatus === BookingStatus::APPROVED,
                    !$oldAppointmentStatusChanged && !$bookingRescheduled
                );
            }

            if (
                !$oldAppointmentStatusChanged &&
                !$bookingRescheduled &&
                $oldAppointment->getStatus()->getValue() === BookingStatus::APPROVED
            ) {
                $applicationNotificationService->sendAppointmentUpdatedNotifications(
                    $oldAppointment,
                    null,
                    !$bookingEmployeeChanged,
                    !$bookingEmployeeChanged
                );
            }

            if ($bookingRescheduled) {
                $webHookService->process(self::TIME_UPDATED, $oldAppointment->toArray(), []);
            }

            return;
        }

        // appointment have other bookings
        // old appointment is canceled OR it's status is changed
        if ($oldAppointment->getStatus()->getValue() === BookingStatus::CANCELED) {
            $applicationIntegrationService->handleAppointment(
                $oldAppointment,
                $oldAppointmentArray,
                ApplicationIntegrationService::APPOINTMENT_DELETED,
                [
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );

            if ($bookingEmployeeChanged) {
                $applicationNotificationService->sendAppointmentUpdatedNotifications(
                    $oldAppointment,
                    $bookingEmployeeChanged,
                    false,
                    true
                );
            }
        } else {
            $applicationIntegrationService->handleAppointment(
                $oldAppointment,
                $oldAppointmentArray,
                ApplicationIntegrationService::BOOKING_CANCELED,
                [
                    ApplicationIntegrationService::SKIP_ZOOM_MEETING => true,
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );

            if ($oldAppointmentStatusChanged) {
                $applicationNotificationService->sendAppointmentProviderStatusNotifications(
                    $oldAppointment
                );
            }

            $applicationNotificationService->sendAppointmentCustomersStatusNotifications(
                $oldAppointment,
                $oldAppointment->getBookings(),
                true,
                true,
                false
            );
        }

        // new appointment is created with booking OR booking is reassigned to another appointment
        if ($commandResult->getData()['newAppointment'] !== null) {
            /** @var Appointment $newAppointment */
            $newAppointment = AppointmentFactory::create($commandResult->getData()['newAppointment']);

            $newAppointmentArray = [];

            $bookingApplicationService->setAppointmentEntities($newAppointment, $appointments);

            $appointments->addItem($newAppointment, $newAppointment->getId()->getValue(), true);

            $applicationIntegrationService->handleAppointment(
                $newAppointment,
                $newAppointmentArray,
                ApplicationIntegrationService::APPOINTMENT_ADDED,
                [
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );

            if ($bookingRescheduled) {
                /** @var CustomerBooking $customerBooking */
                foreach ($newAppointment->getBookings()->getItems() as $customerBooking) {
                    $customerBooking->setIcsFiles(
                        $icsService->getIcsData(Entities::APPOINTMENT, $customerBooking->getId()->getValue(), [], true)
                    );
                }

                $applicationNotificationService->sendAppointmentRescheduleNotifications(
                    $newAppointment,
                    true,
                    true
                );

                $webHookService->process(self::TIME_UPDATED, $newAppointment->toArray(), []);
            } elseif ($bookingEmployeeChanged) {
                $applicationNotificationService->sendAppointmentUpdatedNotifications(
                    $newAppointment,
                    $bookingEmployeeChanged,
                    true,
                    true
                );
            }

            return;
        }

        /** @var Appointment $existingAppointment */
        $existingAppointment = AppointmentFactory::create($commandResult->getData()['existingAppointment']);

        $existingAppointmentArray = [];

        $bookingApplicationService->setAppointmentEntities($existingAppointment, $appointments);

        $appointments->addItem($existingAppointment, $existingAppointment->getId()->getValue(), true);

        $applicationIntegrationService->handleAppointment(
            $existingAppointment,
            $existingAppointmentArray,
            ApplicationIntegrationService::BOOKING_ADDED,
            [
                ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
            ]
        );

        if ($existingAppointmentStatusChanged) {
            $applicationNotificationService->sendAppointmentProviderStatusNotifications(
                $existingAppointment
            );
        }

        $applicationNotificationService->sendAppointmentCustomersStatusNotifications(
            $existingAppointment,
            $existingAppointment->getBookings(),
            true,
            true,
            false
        );

        $existingAppointment->setBookings(new Collection());

        $existingAppointment->getBookings()->addItem($booking);

        if ($bookingRescheduled) {
            /** @var CustomerBooking $customerBooking */
            foreach ($existingAppointment->getBookings()->getItems() as $customerBooking) {
                $booking->setIcsFiles(
                    $icsService->getIcsData(Entities::APPOINTMENT, $customerBooking->getId()->getValue(), [], true)
                );
            }

            $applicationNotificationService->sendAppointmentRescheduleNotifications(
                $existingAppointment,
                false,
                true
            );
        } elseif ($bookingEmployeeChanged && !$existingAppointmentStatusChanged) {
            $applicationNotificationService->sendAppointmentUpdatedNotifications(
                $existingAppointment,
                $bookingEmployeeChanged,
                false,
                $existingAppointmentStatusChanged
            );
        }
    }
}
