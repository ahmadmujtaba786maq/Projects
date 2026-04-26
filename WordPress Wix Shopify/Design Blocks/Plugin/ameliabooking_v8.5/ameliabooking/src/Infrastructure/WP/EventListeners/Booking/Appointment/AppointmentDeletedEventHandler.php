<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Microsoft\Graph\Exception\GraphException;

/**
 * Class AppointmentDeletedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class AppointmentDeletedEventHandler
{
    /** @var string */
    public const APPOINTMENT_DELETED = 'appointmentDeleted';

    /** @var string */
    public const BOOKING_STATUS_UPDATED = 'bookingStatusUpdated';

    /**
     * @param CommandResult $commandResult
     * @param Container $container
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws GraphException
     */
    public static function handle($commandResult, $container)
    {
        /** @var ApplicationIntegrationService $applicationIntegrationService */
        $applicationIntegrationService = $container->get('application.integration.service');
        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $container->get('application.emailNotification.service');
        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $container->get('application.smsNotification.service');
        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $container->get('application.whatsAppNotification.service');
        /** @var SettingsService $settingsService */
        $settingsService = $container->get('domain.settings.service');
        /** @var AbstractWebHookApplicationService $webHookService */
        $webHookService = $container->get('application.webHook.service');
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $container->get('application.booking.booking.service');

        $appointment = $commandResult->getData()[Entities::APPOINTMENT];

        $bookings = $commandResult->getData()['bookingsWithChangedStatus'];

        /** @var Appointment|Event $reservationObject */
        $reservationObject = AppointmentFactory::create($appointment);

        $bookingApplicationService->setReservationEntities($reservationObject);

        $applicationIntegrationService->handleAppointment(
            $reservationObject,
            $appointment,
            ApplicationIntegrationService::APPOINTMENT_DELETED,
            [
                ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => !$reservationObject->getProvider(),
                ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => !$reservationObject->getProvider(),
                ApplicationIntegrationService::SKIP_ZOOM_MEETING     => !$reservationObject->getProvider(),
                ApplicationIntegrationService::SKIP_LESSON_SPACE     => true,
                ApplicationIntegrationService::SKIP_APPLE_CALENDAR   => !$reservationObject->getProvider(),
            ]
        );

        if ($reservationObject->getMicrosoftTeamsUrl() !== null) {
            $appointment['microsoftTeamsUrl'] = $reservationObject->getMicrosoftTeamsUrl();
        }

        $emailNotificationService->sendAppointmentStatusNotifications($appointment, false, false);

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $smsNotificationService->sendAppointmentStatusNotifications($appointment, false, false);
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $whatsAppNotificationService->sendAppointmentStatusNotifications($appointment, false, false);
        }

        if ($bookings) {
            $webHookService->process(self::BOOKING_STATUS_UPDATED, $appointment, $bookings);
        }
    }
}
