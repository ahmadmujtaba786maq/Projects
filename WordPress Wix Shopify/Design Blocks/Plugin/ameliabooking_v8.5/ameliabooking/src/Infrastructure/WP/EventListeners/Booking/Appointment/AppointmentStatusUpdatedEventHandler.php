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
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AppointmentStatusUpdatedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class AppointmentStatusUpdatedEventHandler
{
    /** @var string */
    public const APPOINTMENT_STATUS_UPDATED = 'appointmentStatusUpdated';

    /** @var string */
    public const BOOKING_STATUS_UPDATED = 'bookingStatusUpdated';

    /**
     * @param CommandResult $commandResult
     * @param Container     $container
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws NotFoundException
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

        $appointment = $commandResult->getData()[Entities::APPOINTMENT];

        /** @var Appointment|Event $reservationObject */
        $reservationObject = AppointmentFactory::create($appointment);

        $bookingApplicationService->setReservationEntities($reservationObject);

        $applicationIntegrationService->handleAppointment(
            $reservationObject,
            $appointment,
            ApplicationIntegrationService::APPOINTMENT_STATUS_UPDATED,
            [
                ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => $appointment['status'] === BookingStatus::NO_SHOW,
                ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => $appointment['status'] === BookingStatus::NO_SHOW,
                ApplicationIntegrationService::SKIP_APPLE_CALENDAR   => $appointment['status'] === BookingStatus::NO_SHOW,
            ]
        );

        $bookings = $commandResult->getData()['bookingsWithChangedStatus'];

        // if appointment approved add ics file to bookings
        if ($appointment['status'] === BookingStatus::APPROVED || $appointment['status'] === BookingStatus::PENDING) {
            /** @var IcsApplicationService $icsService */
            $icsService = $container->get('application.ics.service');

            foreach ($appointment['bookings'] as $index => $booking) {
                if ($appointment['bookings'][$index]['isChangedStatus'] === true) {
                    $appointment['bookings'][$index]['icsFiles'] = $icsService->getIcsData(
                        Entities::APPOINTMENT,
                        $booking['id'],
                        [],
                        true
                    );
                }

                if (!empty($appointment['createPaymentLinks'])) {
                    $paymentId = !empty($booking['payments'][0]['id']) ? $booking['payments'][0]['id'] : null;

                    if (!empty($paymentId)) {
                        /** @var ServiceRepository $serviceRepository */
                        $serviceRepository = $container->get('domain.bookable.service.repository');

                        /** @var CustomerRepository $customerRepository */
                        $customerRepository = $container->get('domain.users.customers.repository');

                        $data = [
                            'booking' => $booking,
                            'type' => Entities::APPOINTMENT,
                            'appointment' => $appointment,
                            'paymentId' => $paymentId,
                            'bookable' => $serviceRepository->getById($appointment['serviceId'])->toArray(),
                            'customer' => !empty($booking['customer'])
                                ? $booking['customer']
                                : $customerRepository->getById($booking['customerId'])->toArray()
                        ];

                        /** @var PaymentApplicationService $paymentAS */
                        $paymentAS = $container->get('application.payment.service');

                        $appointment['bookings'][$index]['payments'][0]['paymentLinks'] = $paymentAS->createPaymentLink($data, $index);
                    }
                }
            }
        }

        $emailNotificationService->sendAppointmentStatusNotifications($appointment, false, true);

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $smsNotificationService->sendAppointmentStatusNotifications($appointment, false, true);
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $whatsAppNotificationService->sendAppointmentStatusNotifications($appointment, false, true);
        }

        if ($bookings) {
            if ($appointment['status'] === BookingStatus::CANCELED) {
                $webHookService->process(BookingCanceledEventHandler::BOOKING_CANCELED, $appointment, $bookings);
            } else {
                $webHookService->process(self::BOOKING_STATUS_UPDATED, $appointment, $bookings);
            }
        }
    }
}
