<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BookingEditedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class BookingEditedEventHandler
{
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
     * @throws \Exception
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
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $container->get('application.payment.service');
        /** @var EventRepository $eventRepository */
        $eventRepository = $container->get('domain.booking.event.repository');

        $appointment = $commandResult->getData()[$commandResult->getData()['type']];
        $booking     = $commandResult->getData()[Entities::BOOKING];
        $bookingStatusChanged = $commandResult->getData()['bookingStatusChanged'];
        $sendInvoice          = null;

        if ($bookingStatusChanged) {
            $reservationObject = $eventRepository->getById($appointment['id']);
            $paymentId         = $booking['payments'][0]['id'];

            if ($commandResult->getData()['createPaymentLinks']) {
                $paymentData  = [
                    'booking' => $booking,
                    'type' => Entities::EVENT,
                    'event' => $appointment,
                    'paymentId' => $paymentId,
                    'bookable' => $reservationObject->toArray(),
                    'customer' => $booking['customer']
                ];
                $bookingIndex = array_search($booking['id'], array_column($appointment['bookings'], 'id'));
                if ($bookingIndex !== false && !empty($paymentId)) {
                    $appointment['bookings'][$bookingIndex]['payments'][0]['paymentLinks'] = $paymentAS->createPaymentLink($paymentData, $bookingIndex);
                }
            }


            if (
                $booking['status'] === BookingStatus::APPROVED ||
                $booking['status'] === BookingStatus::CANCELED ||
                $booking['status'] === BookingStatus::REJECTED
            ) {
                $applicationIntegrationService->handleEvent(
                    $reservationObject,
                    $reservationObject->getPeriods(),
                    $reservation,
                    $booking['status'] === BookingStatus::APPROVED
                        ? ApplicationIntegrationService::BOOKING_ADDED
                        : ApplicationIntegrationService::BOOKING_CANCELED,
                    [
                        ApplicationIntegrationService::SKIP_ZOOM_MEETING => true,
                        ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                    ]
                );
            }

            if (
                !empty($paymentId) && $booking['status'] === BookingStatus::APPROVED
                && $settingsService->getSetting('notifications', 'sendInvoice')
            ) {
                $sendInvoice = true;
            }


            $emailNotificationService->sendCustomerBookingNotification($appointment, $booking, $sendInvoice);
            $emailNotificationService->sendProviderBookingNotification($appointment, $booking);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendCustomerBookingNotification($appointment, $booking);
                $smsNotificationService->sendProviderBookingNotification($appointment, $booking);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendCustomerBookingNotification($appointment, $booking);
                $whatsAppNotificationService->sendProviderBookingNotification($appointment, $booking);
            }

            $webHookService->process(self::BOOKING_STATUS_UPDATED, $appointment, [$booking]);
        }
    }
}
