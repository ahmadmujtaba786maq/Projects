<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class ApplicationNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
class ApplicationNotificationService
{
    protected $container;

    /**
     * ApplicationNotificationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Appointment $appointment
     * @param bool        $logNotification
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function sendAppointmentProviderStatusNotifications(
        $appointment,
        $logNotification = true
    ) {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var AppointmentNotificationService $appointmentNotificationService */
        $appointmentNotificationService = $this->container->get('application.notification.appointment.service');

        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $this->container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $this->container->get('application.smsNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->container->get('application.whatsAppNotification.service');

        $appointmentNotificationService->sendProviderStatusNotifications(
            $emailNotificationService,
            $appointment,
            $logNotification
        );

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $appointmentNotificationService->sendProviderStatusNotifications(
                $smsNotificationService,
                $appointment,
                $logNotification
            );
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $appointmentNotificationService->sendProviderStatusNotifications(
                $whatsAppNotificationService,
                $appointment,
                $logNotification
            );
        }
    }

    /**
     * @param Appointment $appointment
     * @param Collection  $bookings
     * @param bool        $logNotification
     * @param bool        $isBackend
     * @param bool        $sendInvoice
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function sendAppointmentCustomersStatusNotifications(
        $appointment,
        $bookings,
        $logNotification = true,
        $isBackend = true,
        $sendInvoice = false,
        $notifyCustomers = true
    ) {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var AppointmentNotificationService $appointmentNotificationService */
        $appointmentNotificationService = $this->container->get('application.notification.appointment.service');

        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $this->container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $this->container->get('application.smsNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->container->get('application.whatsAppNotification.service');

        $appointmentNotificationService->sendCustomersStatusNotifications(
            $emailNotificationService,
            $appointment,
            $bookings,
            $logNotification,
            $isBackend,
            $sendInvoice,
            $notifyCustomers
        );

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $appointmentNotificationService->sendCustomersStatusNotifications(
                $smsNotificationService,
                $appointment,
                $bookings,
                $logNotification,
                $isBackend,
                $sendInvoice
            );
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $appointmentNotificationService->sendCustomersStatusNotifications(
                $whatsAppNotificationService,
                $appointment,
                $bookings,
                $logNotification,
                $isBackend,
                $sendInvoice
            );
        }
    }

    /**
     * @param Appointment $appointment
     * @param bool        $notifyProvider
     * @param bool        $notifyCustomers
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendAppointmentRescheduleNotifications(
        $appointment,
        $notifyProvider = true,
        $notifyCustomers = true
    ) {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var AppointmentNotificationService $appointmentNotificationService */
        $appointmentNotificationService = $this->container->get('application.notification.appointment.service');

        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $this->container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $this->container->get('application.smsNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->container->get('application.whatsAppNotification.service');

        $appointmentNotificationService->sendRescheduledNotifications(
            $emailNotificationService,
            $appointment,
            $notifyProvider,
            $notifyCustomers
        );

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $appointmentNotificationService->sendRescheduledNotifications(
                $smsNotificationService,
                $appointment,
                $notifyProvider,
                $notifyCustomers
            );
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $appointmentNotificationService->sendRescheduledNotifications(
                $whatsAppNotificationService,
                $appointment,
                $notifyProvider,
                $notifyCustomers
            );
        }
    }

    /**
     * @param Appointment $appointment
     * @param int|null    $changedProviderId
     * @param bool        $notifyProvider
     * @param bool        $notifyCustomers
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendAppointmentUpdatedNotifications(
        $appointment,
        $changedProviderId,
        $notifyProvider = true,
        $notifyCustomers = true
    ) {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var AppointmentNotificationService $appointmentNotificationService */
        $appointmentNotificationService = $this->container->get('application.notification.appointment.service');

        /** @var EmailNotificationService $emailNotificationService */
        $emailNotificationService = $this->container->get('application.emailNotification.service');

        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $this->container->get('application.smsNotification.service');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->container->get('application.whatsAppNotification.service');

        if ($changedProviderId) {
            $newProviderId = $appointment->getProviderId()->getValue();

            $appointment->setProviderId(new Id($changedProviderId));
        }

        $appointmentNotificationService->sendUpdatedNotifications(
            $emailNotificationService,
            $appointment,
            $notifyProvider,
            $notifyCustomers
        );

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $appointmentNotificationService->sendUpdatedNotifications(
                $smsNotificationService,
                $appointment,
                $notifyProvider,
                $notifyCustomers
            );
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $appointmentNotificationService->sendUpdatedNotifications(
                $whatsAppNotificationService,
                $appointment,
                $notifyProvider,
                $notifyCustomers
            );
        }

        if ($changedProviderId) {
            $appointment->setProviderId(new Id($newProviderId));
        }
    }
}
