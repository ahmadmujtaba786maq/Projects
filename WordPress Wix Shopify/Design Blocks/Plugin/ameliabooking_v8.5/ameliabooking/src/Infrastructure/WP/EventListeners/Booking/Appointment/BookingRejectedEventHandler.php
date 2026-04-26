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
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BookingRejectedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class BookingRejectedEventHandler
{
    /** @var string */
    public const BOOKING_REJECTED = 'bookingRejected';

    /** @var string */
    public const BOOKING_STATUS_UPDATED = 'bookingStatusUpdated';


    /**
     * @param CommandResult $commandResult
     * @param Container     $container
     *
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
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

        $appointment = $commandResult->getData()[$commandResult->getData()['type']];

        if ($commandResult->getData()['type'] === Entities::APPOINTMENT) {
            $reservationObject = AppointmentFactory::create($appointment);

            $bookingApplicationService->setReservationEntities($reservationObject);

            $applicationIntegrationService->handleAppointment(
                $reservationObject,
                $appointment,
                ApplicationIntegrationService::BOOKING_REJECTED,
                [
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );
        }

        $booking = $commandResult->getData()[Entities::BOOKING];

        $payments = $appointment['bookings'][0]['payments'];
        if ($payments && count($payments)) {
            $booking['payments'] = $payments;
        }

        $emailNotificationService->sendCustomerBookingNotification($appointment, $booking);

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $smsNotificationService->sendCustomerBookingNotification($appointment, $booking);
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $whatsAppNotificationService->sendCustomerBookingNotification($appointment, $booking);
        }

        $appStatusChanged = $commandResult->getData()['appointmentStatusChanged'];

        if ($appStatusChanged === true) {
            $bookingKey = array_search($booking['id'], array_column($appointment['bookings'], 'id'), true);

            $appointment['bookings'][$bookingKey]['isChangedStatus'] = true;

            $appointment['notifyParticipants'] = false;

            $emailNotificationService->sendAppointmentStatusNotifications($appointment, true, true);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendAppointmentStatusNotifications($appointment, true, true);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendAppointmentStatusNotifications($appointment, true, true);
            }
        }

        $webHookService->process(self::BOOKING_STATUS_UPDATED, $appointment, [$booking]);
    }
}
