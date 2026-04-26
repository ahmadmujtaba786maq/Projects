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
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BookingCanceledEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class BookingCanceledEventHandler
{
    /** @var string */
    public const BOOKING_CANCELED = 'bookingCanceled';

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
                ApplicationIntegrationService::BOOKING_CANCELED,
                [
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );
        }

        $booking = $commandResult->getData()[Entities::BOOKING];

        $appStatusChanged = $commandResult->getData()['appointmentStatusChanged'];

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

        if ($commandResult->getData()['type'] === Entities::EVENT) {
            /** @var EventRepository $eventRepository */
            $eventRepository   = $container->get('domain.booking.event.repository');
            $reservationObject = $eventRepository->getById($appointment['id']);

            $applicationIntegrationService->handleEvent(
                $reservationObject,
                $reservationObject->getPeriods(),
                $reservation,
                ApplicationIntegrationService::BOOKING_CANCELED,
                [
                    ApplicationIntegrationService::SKIP_ZOOM_MEETING => true,
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );

            $booking['isChangedStatus'] = true;

            $appointment['bookings'] = [$booking];

            $emailNotificationService->sendProviderEventCancelledNotification($appointment, $booking);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendProviderEventCancelledNotification($appointment, $booking);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendProviderEventCancelledNotification($appointment, $booking);
            }
        }

        if ($appStatusChanged === true) {
            $bookingKey = array_search($booking['id'], array_column($appointment['bookings'], 'id'), true);

            $appointment['bookings'][$bookingKey]['isChangedStatus'] = true;

            $emailNotificationService->sendAppointmentStatusNotifications($appointment, true, true);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendAppointmentStatusNotifications($appointment, true, true);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendAppointmentStatusNotifications($appointment, true, true);
            }
        } else {
            $emailNotificationService->sendAppointmentUpdatedNotifications($appointment, false);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendAppointmentUpdatedNotifications($appointment, false);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendAppointmentUpdatedNotifications($appointment, false);
            }
        }

        $webHookService->process(self::BOOKING_CANCELED, $appointment, [$booking]);
    }
}
