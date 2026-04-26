<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Application\Services\Invoice\AbstractInvoiceApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\NotificationStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;

/**
 * Class AppointmentNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
class AppointmentNotificationService
{
    protected $container;

    /**
     * AppointmentNotificationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param AbstractNotificationService $notificationService
     * @param Appointment                 $appointment
     * @param bool                        $logNotification
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function sendProviderStatusNotifications(
        $notificationService,
        $appointment,
        $logNotification = true
    ) {
        /** @var Collection $providerNotifications */
        $providerNotifications = $notificationService->getByNameAndType(
            "provider_appointment_{$appointment->getStatus()->getValue()}",
            $notificationService->getType()
        );

        $sendDefault = $notificationService->sendDefault($providerNotifications, $appointment->toArray());

        /** @var Notification $providerNotification */
        foreach ($providerNotifications->getItems() as $providerNotification) {
            if (
                $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                $notificationService->checkCustom($providerNotification, $appointment->toArray(), $sendDefault)
            ) {
                $notificationService->sendNotification(
                    $appointment->toArray(),
                    $providerNotification,
                    $logNotification
                );
            }
        }
    }

    /**
     * @param AbstractNotificationService $notificationService
     * @param Appointment                 $appointment
     * @param Collection                  $bookings
     * @param bool                        $logNotification
     * @param bool                        $isBackend
     * @param bool                        $sendInvoice
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function sendCustomersStatusNotifications(
        $notificationService,
        $appointment,
        $bookings,
        $logNotification = true,
        $isBackend = true,
        $sendInvoice = false,
        $notifyCustomers = true
    ) {
        /** @var AbstractInvoiceApplicationService $invoiceService */
        $invoiceService = $this->container->get('application.invoice.service');

        /** @var Collection $statusNotifications */
        $statusNotifications = new Collection();

        /** @var CustomerBooking $booking */
        foreach ($bookings->getItems() as $bookingKey => $booking) {
            if ($booking->isChangedStatus() && $booking->isChangedStatus()->getValue()) {
                $notificationStatus = $appointment->getStatus()->getValue() === BookingStatus::PENDING &&
                (
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $booking->getStatus()->getValue() === BookingStatus::PENDING
                )
                    ? BookingStatus::PENDING
                    : $booking->getStatus()->getValue();

                if (!$statusNotifications->keyExists($notificationStatus)) {
                    $statusNotifications->addItem(
                        $notificationService->getByNameAndType(
                            "customer_appointment_{$notificationStatus}",
                            $notificationService->getType()
                        ),
                        $notificationStatus
                    );
                }

                /** @var Collection $customerNotifications */
                $customerNotifications = $statusNotifications->getItem($notificationStatus);

                $sendDefault = $notificationService->sendDefault($customerNotifications, $appointment->toArray());

                /** @var Notification $customerNotification */
                foreach ($customerNotifications->getItems() as $customerNotification) {
                    if (
                        $customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                        $notificationService->checkCustom(
                            $customerNotification,
                            $appointment->toArray(),
                            $sendDefault
                        ) && $notifyCustomers
                    ) {
                        $notificationService->sendNotification(
                            array_merge(
                                $appointment->toArray(),
                                [
                                    'bookings'  => $bookings->toArray(),
                                    'isBackend' => $isBackend,
                                ]
                            ),
                            $customerNotification,
                            $logNotification,
                            $bookingKey,
                            null,
                            (
                                $sendInvoice &&
                                $booking->getPayments()->length() &&
                                $booking->getPayments()->keyExists(0)
                            )
                            ? $invoiceService->generateInvoice(
                                $booking->getPayments()->getItem(0)->getId()->getValue()
                            )
                            : null
                        );
                    }
                }
            }
        }
    }

    /**
     * @param AbstractNotificationService $notificationService
     * @param Appointment                 $appointment
     * @param bool                        $notifyProvider
     * @param bool                        $notifyCustomers
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendRescheduledNotifications(
        $notificationService,
        $appointment,
        $notifyProvider = true,
        $notifyCustomers = true
    ) {
        if ($notifyProvider) {
            /** @var Collection $providerNotifications */
            $providerNotifications = $notificationService->getByNameAndType(
                "provider_appointment_rescheduled",
                $notificationService->getType()
            );

            $sendDefault = $notificationService->sendDefault($providerNotifications, $appointment->toArray());

            /** @var Notification $providerNotification */
            foreach ($providerNotifications->getItems() as $providerNotification) {
                if (
                    $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                    $notificationService->checkCustom($providerNotification, $appointment->toArray(), $sendDefault)
                ) {
                    $notificationService->sendNotification(
                        $appointment->toArray(),
                        $providerNotification,
                        true
                    );
                }
            }
        }

        if ($notifyCustomers && $appointment->isNotifyParticipants()) {
            /** @var Collection $customerNotifications */
            $customerNotifications = $notificationService->getByNameAndType(
                "customer_appointment_rescheduled",
                $notificationService->getType()
            );

            $sendDefault = $notificationService->sendDefault($customerNotifications, $appointment->toArray());

            /** @var Notification $customerNotification */
            foreach ($customerNotifications->getItems() as $customerNotification) {
                if (
                    $customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                    $notificationService->checkCustom($customerNotification, $appointment->toArray(), $sendDefault)
                ) {
                    /** @var CustomerBooking $booking */
                    foreach ($appointment->getBookings()->getItems() as $bookingKey => $booking) {
                        if (
                            (!$booking->isNew() || !$booking->isNew()->getValue()) &&
                            (
                                $booking->getStatus()->getValue() === BookingStatus::APPROVED ||
                                $booking->getStatus()->getValue() === BookingStatus::PENDING
                            )
                        ) {
                            $notificationService->sendNotification(
                                $appointment->toArray(),
                                $customerNotification,
                                true,
                                $bookingKey
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param AbstractNotificationService $notificationService
     * @param Appointment                 $appointment
     * @param bool                        $notifyProvider
     * @param bool                        $notifyCustomers
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendUpdatedNotifications(
        $notificationService,
        $appointment,
        $notifyProvider,
        $notifyCustomers
    ) {
        if ($notifyProvider) {
            /** @var Collection $providerNotifications */
            $providerNotifications = $notificationService->getByNameAndType(
                "provider_appointment_updated",
                $notificationService->getType()
            );

            $sendDefault = $notificationService->sendDefault($providerNotifications, $appointment->toArray());

            /** @var Notification $providerNotification */
            foreach ($providerNotifications->getItems() as $providerNotification) {
                if (
                    $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                    $notificationService->checkCustom($providerNotification, $appointment->toArray(), $sendDefault)
                ) {
                    $notificationService->sendNotification(
                        $appointment->toArray(),
                        $providerNotification,
                        true
                    );
                }
            }
        }

        if ($notifyCustomers && $appointment->isNotifyParticipants()) {
            /** @var Collection $customerNotifications */
            $customerNotifications = $notificationService->getByNameAndType(
                "customer_appointment_updated",
                $notificationService->getType()
            );

            $sendDefault = $notificationService->sendDefault($customerNotifications, $appointment->toArray());

            /** @var Notification $customerNotification */
            foreach ($customerNotifications->getItems() as $customerNotification) {
                if (
                    $customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                    $notificationService->checkCustom($customerNotification, $appointment->toArray(), $sendDefault)
                ) {
                    /** @var CustomerBooking $booking */
                    foreach ($appointment->getBookings()->getItems() as $bookingKey => $booking) {
                        if (
                            $booking->getStatus()->getValue() === BookingStatus::APPROVED &&
                            (!$booking->isNew() || !$booking->isNew()->getValue()) &&
                            $booking->isUpdated() &&
                            $booking->isUpdated()->getValue()
                        ) {
                            $notificationService->sendNotification(
                                $appointment->toArray(),
                                $customerNotification,
                                true,
                                $bookingKey
                            );
                        }
                    }
                }
            }
        }
    }
}
