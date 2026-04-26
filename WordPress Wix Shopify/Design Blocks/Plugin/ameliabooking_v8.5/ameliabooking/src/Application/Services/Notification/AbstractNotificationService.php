<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Notification;

use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Invoice\AbstractInvoiceApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\NotificationSendTo;
use AmeliaBooking\Domain\ValueObjects\String\NotificationStatus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationLogRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationsToEntitiesRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractNotificationService
 *
 * @package AmeliaBooking\Application\Services\Notification
 */
abstract class AbstractNotificationService
{
    /** @var Container */
    protected $container;

    /** @var string */
    protected $type;

    /** @var array */
    protected $sendNotifications = true;

    /** @var array */
    protected $preparedNotificationData = [];

    /**
     * AbstractNotificationService constructor.
     *
     * @param Container $container
     * @param string    $type
     */
    public function __construct(Container $container, $type)
    {
        $this->container = $container;

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param bool $value
     */
    public function setSend($value)
    {
        $this->sendNotifications = $value;
    }

    /**
     * @return bool
     */
    public function getSend()
    {
        return $this->sendNotifications;
    }

    /**
     * @return array
     */
    protected function getPreparedNotificationData()
    {
        return $this->preparedNotificationData;
    }

    /**
     * @param array $data
     */
    protected function addPreparedNotificationData($data)
    {
        $this->preparedNotificationData[] = $data;
    }

    /**
     * @return void
     */
    abstract public function sendPreparedNotifications();

    /**
     * @param array        $appointmentArray
     * @param Notification $notification
     * @param bool         $logNotification
     * @param null         $bookingKey
     *
     * @return mixed
     */
    abstract public function sendNotification(
        $appointmentArray,
        $notification,
        $logNotification,
        $bookingKey = null,
        $allBookings = null,
        $invoice = []
    );


    /**
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws Exception
     */
    abstract public function sendBirthdayGreetingNotifications();

    /**
     *
     * @param string $name
     * @param string $type
     *
     * @return Collection
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByNameAndType($name, $type)
    {
        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');
        /** @var NotificationsToEntitiesRepository $notificationEntitiesRepo */
        $notificationEntitiesRepo = $this->container->get('domain.notificationEntities.repository');

        /** @var Collection $notifications */
        $notifications = $notificationRepo->getByNameAndType($name, $type);
        /** @var Notification $notification */
        foreach ($notifications->getItems() as $notification) {
            if ($notification->getCustomName() !== null) {
                $notification->setEntityIds($notificationEntitiesRepo->getEntities($notification->getId()->getValue()));
            }
        }

        return $notifications;
    }

    /**
     *
     * @param int $id
     *
     * @return Notification
     *
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function getById($id)
    {
        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        return $notificationRepo->getById($id);
    }

    /**
     * @param array $appointmentArray
     * @param bool $forcedStatusChange - True when appointment status is changed to 'pending' because minimum capacity
     * condition is not satisfied
     * @param bool $logNotification
     * @param bool $isBackend
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendAppointmentStatusNotifications($appointmentArray, $forcedStatusChange, $logNotification, $isBackend = false, $sendInvoice = false)
    {
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        // Notify provider
        /** @var Collection $providerNotifications */
        $providerNotifications = $this->getByNameAndType(
            "provider_{$appointmentArray['type']}_{$appointmentArray['status']}",
            $this->type
        );

        $sendDefault = $this->sendDefault($providerNotifications, $appointmentArray);

        $appointmentArray['sendCF'] = true;

        $dontSend = $appointmentArray['type'] === Entities::EVENT && $appointmentArray['status'] === BookingStatus::REJECTED
            && DateTimeService::getNowDateTimeObject() >
            DateTimeService::getCustomDateTimeObject($appointmentArray['periods'][count($appointmentArray['periods']) - 1]['periodStart']);

        /** @var Notification $providerNotification */
        foreach ($providerNotifications->getItems() as $providerNotification) {
            if ($providerNotification && $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED && !$dontSend) {
                if (!$this->checkCustom($providerNotification, $appointmentArray, $sendDefault)) {
                    continue;
                }
                $this->sendNotification(
                    $appointmentArray,
                    $providerNotification,
                    $logNotification
                );
            }
        }

        // Notify customers
        if ($appointmentArray['notifyParticipants']) {

            /** @var Collection $customerNotifications */
            $customerNotifications = $this->getByNameAndType(
                "customer_{$appointmentArray['type']}_{$appointmentArray['status']}",
                $this->type
            );

            $sendDefault = $this->sendDefault($customerNotifications, $appointmentArray);

            foreach ($customerNotifications->getItems() as $customerNotification) {
                if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED && !$dontSend) {
                    if (!$this->checkCustom($customerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    // If appointment status is changed to 'pending' because minimum capacity condition is not satisfied,
                    // return all 'approved' bookings and send them notification that appointment is now 'pending'.
                    if ($forcedStatusChange === true) {
                        $appointmentArray['bookings'] = $bookingAS->filterApprovedBookings($appointmentArray['bookings']);
                    }

                    $appointmentArray['isBackend'] = $isBackend;
                    // Notify each customer from customer bookings
                    foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                        if (
                            !$appointmentArray['bookings'][$bookingKey]['isChangedStatus'] ||
                            (
                                isset($appointmentArray['bookings'][$bookingKey]['skipNotification']) &&
                                $appointmentArray['bookings'][$bookingKey]['skipNotification']
                            )
                        ) {
                            continue;
                        }

                        $invoice = null;
                        if ($sendInvoice) {
                            /** @var AbstractInvoiceApplicationService $invoiceService */
                            $invoiceService = $this->container->get('application.invoice.service');

                            $invoice = $invoiceService->generateInvoice($appointmentArray['bookings'][$bookingKey]['payments'][0]['id']);
                        }

                        $this->sendNotification(
                            $appointmentArray,
                            $customerNotification,
                            $logNotification,
                            $bookingKey,
                            null,
                            $invoice
                        );
                    }
                }
            }
        }
    }

    /**
     * @param $appointmentArray
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendAppointmentRescheduleNotifications($appointmentArray)
    {
        // Notify customers
        if ($appointmentArray['notifyParticipants']) {

            /** @var Collection $customerNotifications */
            $customerNotifications = $this->getByNameAndType(
                "customer_{$appointmentArray['type']}_rescheduled",
                $this->type
            );

            $sendDefault = $this->sendDefault($customerNotifications, $appointmentArray);
            foreach ($customerNotifications->getItems() as $customerNotification) {
                if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    if (!$this->checkCustom($customerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    // Notify each customer from customer bookings
                    foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                        $this->sendNotification(
                            $appointmentArray,
                            $customerNotification,
                            true,
                            $bookingKey
                        );
                    }
                }
            }
        }

        if (empty($appointmentArray['employee_changed'])) {
            // Notify provider
            /** @var Collection $providerNotifications */
            $providerNotifications = $this->getByNameAndType(
                "provider_{$appointmentArray['type']}_rescheduled",
                $this->type
            );

            $sendDefault = $this->sendDefault($providerNotifications, $appointmentArray);
            foreach ($providerNotifications->getItems() as $providerNotification) {
                if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    if (!$this->checkCustom($providerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    $this->sendNotification(
                        $appointmentArray,
                        $providerNotification,
                        true
                    );
                }
            }
        }
    }

    /**
     * @param $appointmentArray
     * @param $appointmentRescheduled
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendAppointmentUpdatedNotifications($appointmentArray, $appointmentRescheduled = null)
    {
        // Notify customers
        if ($appointmentArray['notifyParticipants'] && !$appointmentRescheduled) {

            /** @var Collection $customerNotifications */
            $customerNotifications = $this->getByNameAndType(
                "customer_{$appointmentArray['type']}_updated",
                $this->type
            );

            $sendDefault = $this->sendDefault($customerNotifications, $appointmentArray);
            foreach ($customerNotifications->getItems() as $customerNotification) {
                if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    if (!$this->checkCustom($customerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    // Notify each customer from customer bookings
                    foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                        if (
                            $appointmentArray['bookings'][$bookingKey]['status'] === BookingStatus::APPROVED &&
                            $appointmentArray['status'] === BookingStatus::APPROVED &&
                            ($appointmentArray['bookings'][$bookingKey]['isUpdated'] || $appointmentArray['type'] === Entities::EVENT)
                        ) {
                            $this->sendNotification(
                                $appointmentArray,
                                $customerNotification,
                                true,
                                $bookingKey
                            );
                        }
                    }
                }
            }
        }

        if (!empty($appointmentArray['employee_changed'])) {
            $appointmentArray['providerId'] = $appointmentArray['employee_changed'];
        }

        if ($appointmentArray['status'] === BookingStatus::APPROVED) {
            /** @var Collection $providerNotifications */
            $providerNotifications = $this->getByNameAndType(
                "provider_{$appointmentArray['type']}_updated",
                $this->type
            );

            $sendDefault = $this->sendDefault($providerNotifications, $appointmentArray);
            foreach ($providerNotifications->getItems() as $providerNotification) {
                if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    if (!$this->checkCustom($providerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    $this->sendNotification(
                        $appointmentArray,
                        $providerNotification,
                        true
                    );
                }
            }
        }
    }

    /**
     * @param array $appointmentArray
     * @param array $bookingArray
     * @param bool $logNotification
     * @param array $invoice
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendBookingAddedNotifications($appointmentArray, $bookingArray, $logNotification, $invoice = null)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $defaultStatus = BookingStatus::WAITING !== $bookingArray['status'] ? $appointmentArray['status'] : $bookingArray['status'];

        if ($appointmentArray['type'] !== Entities::EVENT && $defaultStatus === BookingStatus::APPROVED && empty($bookingArray['packageBookingFromBackend'])) {

            /** @var ServiceRepository $serviceRepository */
            $serviceRepository = $this->container->get('domain.bookable.service.repository');

            $service = $serviceRepository->getById($appointmentArray['serviceId']);

            $defaultStatus =
                ($service->getSettings() && !empty(json_decode($service->getSettings()->getValue(), true)['general']['defaultAppointmentStatus'])) ?
                    json_decode($service->getSettings()->getValue(), true)['general']['defaultAppointmentStatus'] :
                    $settingsService->getSetting('general', 'defaultAppointmentStatus');
        } elseif (
            $appointmentArray['type'] === Entities::EVENT &&
            $settingsService->getSetting('general', 'defaultEventStatus') === BookingStatus::PENDING
        ) {
            $defaultStatus = $bookingArray['status'];
        }

        $customerNotifications = $this->getByNameAndType(
            "customer_{$appointmentArray['type']}_{$defaultStatus}",
            $this->type
        );

        $sendDefault = $this->sendDefault($customerNotifications, $appointmentArray);

        foreach ($customerNotifications->getItems() as $customerNotification) {
            if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                if (!$this->checkCustom($customerNotification, $appointmentArray, $sendDefault)) {
                    continue;
                }

                // Notify customer that scheduled the appointment
                $this->sendNotification(
                    $appointmentArray,
                    $customerNotification,
                    $logNotification,
                    $bookingArray ? array_search($bookingArray['id'], array_column($appointmentArray['bookings'], 'id'), true) : null,
                    null,
                    $invoice
                );
            }
        }

        // Notify provider
        $providerNotifications = $this->getByNameAndType(
            "provider_{$appointmentArray['type']}_{$defaultStatus}",
            $this->type
        );

        $sendDefault = $this->sendDefault($providerNotifications, $appointmentArray);
        foreach ($providerNotifications->getItems() as $providerNotification) {
            if ($providerNotification && $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                if (!$this->checkCustom($providerNotification, $appointmentArray, $sendDefault)) {
                    continue;
                }
                $allBookings = null;
                if ($appointmentArray['type'] === Entities::EVENT) {
                    $allBookings = $appointmentArray['bookings'];
                    $appointmentArray['bookings'] = [$bookingArray];
                }
                $this->sendNotification(
                    $appointmentArray,
                    $providerNotification,
                    $logNotification,
                    null,
                    $allBookings
                );
            }
        }
    }

    /**
     * Notify the customer when he changes his booking status.
     *
     * @param $appointmentArray
     * @param $bookingArray
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendCustomerBookingNotification($appointmentArray, $bookingArray, $sendInvoice = false)
    {
        // Notify customers
        if ($appointmentArray['notifyParticipants']) {
            $customerNotifications = $this->getByNameAndType("customer_{$appointmentArray['type']}_{$bookingArray['status']}", $this->type);

            $sendDefault = $this->sendDefault($customerNotifications, $appointmentArray);
            foreach ($customerNotifications->getItems() as $customerNotification) {
                if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    if (!$this->checkCustom($customerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    // Notify customer
                    $bookingKey = array_search(
                        $bookingArray['id'],
                        array_column($appointmentArray['bookings'], 'id'),
                        true
                    );

                    $invoice = null;

                    if ($sendInvoice) {
                        /** @var AbstractInvoiceApplicationService $invoiceService */
                        $invoiceService = $this->container->get('application.invoice.service');

                        $invoice = $invoiceService->generateInvoice($appointmentArray['bookings'][$bookingKey]['payments'][0]['id']);
                    }

                    $this->sendNotification(
                        $appointmentArray,
                        $customerNotification,
                        true,
                        $bookingKey,
                        null,
                        $invoice
                    );
                }
            }
        }
    }

    /**
     * Notify the provider when he changes his booking status.
     *
     * @param $appointmentArray
     * @param $bookingArray
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendProviderBookingNotification($appointmentArray, $bookingArray)
    {
        // Notify provider
        if ($appointmentArray['notifyParticipants'] && $bookingArray['status'] !== BookingStatus::REJECTED) {
            $providerNotifications = $this->getByNameAndType("provider_{$appointmentArray['type']}_{$bookingArray['status']}", $this->type);

            $sendDefault = $this->sendDefault($providerNotifications, $appointmentArray);
            foreach ($providerNotifications->getItems() as $customerNotification) {
                if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                    if (!$this->checkCustom($customerNotification, $appointmentArray, $sendDefault)) {
                        continue;
                    }
                    // Notify provider
                    $bookingKey = array_search(
                        $bookingArray['id'],
                        array_column($appointmentArray['bookings'], 'id'),
                        true
                    );

                    $this->sendNotification(
                        $appointmentArray,
                        $customerNotification,
                        true,
                        $bookingKey
                    );
                }
            }
        }
    }

    /**
     * Notify the provider when the customer cancels event booking.
     *
     * @param $eventArray
     * @param $bookingArray
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendProviderEventCancelledNotification($eventArray, $bookingArray)
    {
        $providerNotifications = $this->getByNameAndType(
            "provider_event_canceled",
            $this->type
        );

        $eventArray['bookings'] = [$bookingArray];

        $sendDefault = $this->sendDefault($providerNotifications, $eventArray);
        foreach ($providerNotifications->getItems() as $providerNotification) {
            if ($providerNotification && $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                if (!$this->checkCustom($providerNotification, $eventArray, $sendDefault)) {
                    continue;
                }
                $this->sendNotification(
                    $eventArray,
                    $providerNotification,
                    false,
                    null
                );
            }
        }
    }

    /**
     * Returns an array of next day reminder notifications that have to be sent to customers with cron
     *
     * @param string $entityType
     *
     * @return void
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function sendNextDayReminderNotifications($entityType)
    {
        /** @var NotificationLogRepository $notificationLogRepo */
        $notificationLogRepo = $this->container->get('domain.notificationLog.repository');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $customerNotifications  = $this->getByNameAndType("customer_{$entityType}_next_day_reminder", $this->type);
        $customerNotifications2 = $this->getByNameAndType("customer_{$entityType}_scheduled", $this->type);

        foreach ($customerNotifications2->getItems() as $notification) {
            $customerNotifications->addItem($notification);
        }

        $reminderStatuses = ['approved'];

        if ($settingsService->getSetting('notifications', 'pendingReminder')) {
            $reminderStatuses[] = 'pending';
        }

        $reservations = new Collection();

        /** @var Notification $customerNotification */
        foreach ($customerNotifications->getItems() as $customerNotification) {
            // Check if notification is enabled and it is time to send notification
            if (
                $customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                $customerNotification->getTime() &&
                DateTimeService::getNowDateTimeObject() >=
                DateTimeService::getCustomDateTimeObject($customerNotification->getTime()->getValue())
            ) {
                switch ($entityType) {
                    case Entities::APPOINTMENT:
                        $reservations = $notificationLogRepo->getCustomersNextDayAppointments(
                            $customerNotification->getId()->getValue(),
                            $customerNotification->getCustomName() === null,
                            $reminderStatuses
                        );

                        break;
                    case Entities::EVENT:
                        $reservations =
                            $notificationLogRepo->getCustomersNextDayEvents(
                                $customerNotification->getId()->getValue(),
                                $customerNotification->getCustomName() === null
                            );

                        break;
                }

                $approvedReservations = new Collection();
                foreach ($reservations->getItems() as $appointment) {
                    if ($appointment->getStatus()->getValue() === BookingStatus::APPROVED) {
                        $approvedReservations->addItem($appointment);
                    }
                }

                try {
                    $this->sendBookingsNotifications($customerNotification, $approvedReservations, true);
                } catch (\Exception $e) {
                }
            }
        }


        /** @var Collection $providerNotifications */
        $providerNotifications  = $this->getByNameAndType("provider_{$entityType}_next_day_reminder", $this->type);
        $providerNotifications2 = $this->getByNameAndType("provider_{$entityType}_scheduled", $this->type);

        foreach ($providerNotifications2->getItems() as $notification) {
            $providerNotifications->addItem($notification);
        }

        /** @var Notification $providerNotification */
        foreach ($providerNotifications->getItems() as $providerNotification) {
            // Check if notification is enabled and it is time to send notification
            if (
                $providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED &&
                $providerNotification->getTime() &&
                DateTimeService::getNowDateTimeObject() >=
                DateTimeService::getCustomDateTimeObject($providerNotification->getTime()->getValue())
            ) {
                switch ($entityType) {
                    case Entities::APPOINTMENT:
                        $reservations = $notificationLogRepo->getProvidersNextDayAppointments(
                            $providerNotification->getId()->getValue(),
                            $providerNotification->getCustomName() === null,
                            $reminderStatuses
                        );

                        break;
                    case Entities::EVENT:
                        $reservations =
                            $notificationLogRepo->getProvidersNextDayEvents(
                                $providerNotification->getId()->getValue(),
                                $providerNotification->getCustomName() === null
                            );

                        break;
                }

                foreach ((array)$reservations->toArray() as $reservationArray) {
                    if (!$this->checkCustom($providerNotification, $reservationArray, true)) {
                        continue;
                    }
                    if ($providerNotification->getCustomName() === null && !$this->checkShouldSend($reservationArray, true, NotificationSendTo::PROVIDER)) {
                        continue;
                    }

                    $bookingArray = $reservationArray['bookings'][count($reservationArray['bookings']) - 1];
                    /** @var CustomerBooking $bookingObject */
                    $bookingObject    = $bookingArray ? CustomerBookingFactory::create($bookingArray) : null;
                    $reservationStart =
                        $entityType === Entities::APPOINTMENT ? $reservationArray['bookingStart'] : $reservationArray['periods'][0]['periodStart'];

                    if ($this->pastMinimumTimeBeforeBooking($providerNotification, $bookingObject, $reservationStart)) {
                        continue;
                    }

                    $reservationArray['sendCF'] = true;

                    try {
                        $this->sendNotification(
                            $reservationArray,
                            $providerNotification,
                            true
                        );
                    } catch (\Exception $e) {
                    }
                }
            }
        }
    }

    /**
     * @param int    $entityId
     * @param string $entityType
     * @param int    $userId
     * @param string $userType
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function invalidateSentScheduledNotifications($entityId, $entityType, $userId, $userType)
    {
        /** @var NotificationLogRepository $notificationLogRepo */
        $notificationLogRepo = $this->container->get('domain.notificationLog.repository');

        $templates = [
            "{$userType}_{$entityType}_next_day_reminder",
            "{$userType}_{$entityType}_scheduled",
            "{$userType}_{$entityType}_scheduled_%",
        ];

        $notificationsIds = [];

        foreach ($templates as $template) {
            /** @var Collection $notifications */
            $notifications = $this->getByNameAndType($template, $this->type);

            $notificationsIds = array_merge($notificationsIds, $notifications->keys());
        }

        $notificationLogRepo->invalidateSentEmails($entityId, $entityType, $userId, array_unique($notificationsIds));
    }

    /**
     * @param string $entityType
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendScheduledNotifications($entityType)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        /** @var Collection $notifications */
        $notifications  = $this->getByNameAndType("customer_{$entityType}_follow_up", $this->type);
        $notifications2 = $this->getByNameAndType("customer_{$entityType}_scheduled_%", $this->type);
        foreach ($notifications2->getItems() as $notification) {
            $notifications->addItem($notification);
        }
        $notifications2 = $this->getByNameAndType("provider_{$entityType}_scheduled_%", $this->type);
        foreach ($notifications2->getItems() as $notification) {
            $notifications->addItem($notification);
        }

        $reminderStatuses = ['approved'];

        if ($settingsService->getSetting('notifications', 'pendingReminder')) {
            $reminderStatuses[] = 'pending';
        }

        /** @var Notification $notification */
        foreach ($notifications->getItems() as $notification) {
            if ($notification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                /** @var NotificationLogRepository $notificationLogRepo */
                $notificationLogRepo = $this->container->get('domain.notificationLog.repository');

                $reservations = new Collection();

                switch ($entityType) {
                    case Entities::APPOINTMENT:
                        $reservations = $notificationLogRepo->getScheduledAppointments(
                            $notification,
                            $reminderStatuses
                        );

                        break;
                    case Entities::EVENT:
                        /** @var Collection $reservations */
                        $reservations = $notificationLogRepo->getScheduledEvents($notification);

                        /** @var EventApplicationService $eventAS */
                        $eventAS = $this->container->get('application.booking.event.service');

                        /** @var Collection $reservations */
                        $reservations = $reservations->length() ? $eventAS->getEventsByIds(
                            $reservations->keys(),
                            [
                                'fetchEventsPeriods'    => true,
                                'fetchEventsProviders'  => true,
                                'fetchBookings'         => true,
                                'fetchBookingsCoupons'  => true,
                                'fetchBookingsPayments' => true,
                            ]
                        ) : new Collection();

                        break;
                }

                $approvedReservations = new Collection();
                foreach ($reservations->getItems() as $appointment) {
                    if ($appointment->getStatus()->getValue() === BookingStatus::APPROVED) {
                        $approvedReservations->addItem($appointment);
                    }
                }

                try {
                    $this->sendBookingsNotifications($notification, $approvedReservations, $notification->getTimeBefore() !== null);
                } catch (\Exception $e) {
                }
            }
        }
    }


    /**
     *
     * @param Notification $notification
     * @param CustomerBooking $booking
     * @param string $appointmentStart
     *
     */
    private function pastMinimumTimeBeforeBooking($notification, $booking, $appointmentStart)
    {
        if (
            $booking && $booking->getCreated() && $notification->getMinimumTimeBeforeBooking() && $notification->getMinimumTimeBeforeBooking()->getValue() &&
            json_decode($notification->getMinimumTimeBeforeBooking()->getValue())
        ) {
            $minimumTime = json_decode($notification->getMinimumTimeBeforeBooking()->getValue(), true);
            $seconds     = 1;
            switch ($minimumTime['period']) {
                case 'minutes':
                    $seconds = 60;
                    break;
                case 'hours':
                    $seconds = 60 * 60;
                    break;
                case 'days':
                    $seconds = 24 * 60 * 60;
                    break;
                case 'weeks':
                    $seconds = 7 * 24 * 60 * 60;
                    break;
                case 'months':
                    $seconds = 30 * 7 * 24 * 60 * 60;
                    break;
            }
            $time = $minimumTime['amount'] * $seconds;
            if (
                DateTimeService::getCustomDateTimeObject($appointmentStart)->modify('-' . $time . ' second')
                <= DateTimeService::getCustomDateTimeObject($booking->getCreated()->getValue()->format('Y-m-d H:i:s'))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send passed notification for all passed bookings and save log in the database
     *
     * @param Notification $notification
     * @param Collection $appointments
     * @param bool $before
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    private function sendBookingsNotifications($notification, $appointments, $before)
    {
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var array $appointmentArray */
        foreach ($appointments->toArray() as $appointmentArray) {
            if (!$this->checkCustom($notification, $appointmentArray, true)) {
                continue;
            }
            if ($notification->getCustomName() === null && !$this->checkShouldSend($appointmentArray, $before, $notification->getSendTo()->getValue())) {
                continue;
            }

            $appointmentArray['sendCF'] = true;

            /** @var BookingApplicationService $bookingApplicationService */
            $bookingApplicationService = $this->container->get('application.booking.booking.service');
            $data = $appointmentArray;
            $reservationObject = $bookingApplicationService->getReservationEntity($appointmentArray);

            $reservationStart =
                $appointmentArray['type'] === Entities::APPOINTMENT ? $appointmentArray['bookingStart'] : $appointmentArray['periods'][0]['periodStart'];

            if ($notification->getSendTo()->getValue() === NotificationSendTo::PROVIDER) {
                /** @var CustomerBooking $bookingObject */
                $bookingObject =
                    $reservationObject->getBookings()->getItem($reservationObject->getBookings()->keys()[$reservationObject->getBookings()->length() - 1]);

                if ($this->pastMinimumTimeBeforeBooking($notification, $bookingObject, $reservationStart)) {
                    continue;
                }

                $this->sendNotification(
                    $appointmentArray,
                    $notification,
                    true
                );
            } else {
                if ($appointmentArray['type'] === Entities::APPOINTMENT) {
                    $data['bookable'] = $reservationObject->getService()->toArray();
                } else {
                    $data['bookable'] = $appointmentArray;
                }

                // Notify each customer from customer bookings
                foreach (array_keys($appointmentArray['bookings']) as $bookingKey) {
                    /** @var CustomerBooking $bookingObject */
                    $bookingObject = $reservationObject->getBookings()->getItem($reservationObject->getBookings()->keys()[$bookingKey]);

                    if ($appointmentArray['type'] === 'event' && $bookingObject->getStatus()->getValue() !== BookingStatus::APPROVED) {
                        continue;
                    }

                    if (
                        (!array_key_exists('createPaymentLinks', $appointmentArray) || !empty($appointmentArray['createPaymentLinks'])) &&
                        $notification->getContent() &&
                        $notification->getContent()->getValue() &&
                        strpos($notification->getContent()->getValue(), '%payment_link_') !== false
                    ) {
                        $data['booking']  = $bookingObject ? $bookingObject->toArray() : $appointmentArray['bookings'][$bookingKey];
                        $data['customer'] =  $data['booking']['customer'];
                        $data[$appointmentArray['type']] = $appointmentArray;
                        $data['paymentId'] = $appointmentArray['bookings'][$bookingKey]['payments'][0]['id'];
                        $appointmentArray['bookings'][$bookingKey]['payments'][0]['paymentLinks'] = $paymentAS->createPaymentLink($data, $bookingKey);
                    }

                    if ($this->pastMinimumTimeBeforeBooking($notification, $bookingObject, $reservationStart)) {
                        continue;
                    }

                    $this->sendNotification(
                        $appointmentArray,
                        $notification,
                        true,
                        $bookingKey
                    );
                }
            }
        }
    }

    /**
     * Check if schedule default notification should be sent
     *
     * @param array $appointmentArray
     * @param bool $before
     * @param string $sendTo
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     *
     * return bool
     *
     */
    private function checkShouldSend($appointmentArray, $before, $sendTo)
    {
        $time          = $before ? 'timeBefore' : 'timeAfter';
        $entityId      = $appointmentArray['type'] === Entities::EVENT ? $appointmentArray['id'] : $appointmentArray['serviceId'];
        $notifications = $this->getByNameAndType("{$sendTo}_{$appointmentArray['type']}_scheduled_%", $this->type);
        $parentId      = $appointmentArray['parentId'];
        return empty(
            array_filter(
                $notifications->toArray(),
                function ($a) use (&$entityId, &$time, &$parentId) {
                    return $a['customName'] && $a[$time] && $a['sendOnlyMe'] &&
                        ($a['entityIds'] === null || in_array($entityId, $a['entityIds']) || ($parentId && in_array($parentId, $a['entityIds'])));
                }
            )
        );
    }

    /**
     * Check if custom notification should be sent
     *
     * @param Notification $notification
     * @param array   $appointmentArray
     *
     * @return bool
     *
     */
    public function checkCustom($notification, $appointmentArray, $sendDefault)
    {
        if (!$sendDefault && !$notification->getCustomName()) {
            return false;
        }
        if ($notification->getCustomName() && $notification->getEntityIds()) {
            $entityId = $appointmentArray['type'] === Entities::EVENT ? $appointmentArray['id'] : $appointmentArray['serviceId'];
            if (!in_array($entityId, $notification->getEntityIds())) {
                if (!in_array($appointmentArray['parentId'], $notification->getEntityIds())) {
                    //Shouldn't be sent
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if default notification should be sent
     *
     * @param Collection $notifications
     * @param array   $appointmentArray
     *
     * @return bool
     *
     */
    public function sendDefault($notifications, $appointmentArray)
    {
        $entityId = $appointmentArray['type'] === Entities::EVENT ? $appointmentArray['id'] : $appointmentArray['serviceId'];
        $parentId = $appointmentArray['parentId'];
        return empty(
            array_filter(
                $notifications->toArray(),
                function ($a) use (&$entityId, &$parentId) {
                    return $a['customName'] && $a['sendOnlyMe'] &&
                        ($a['entityIds'] === null || in_array($entityId, $a['entityIds']) || ($parentId && in_array($parentId, $a['entityIds'])));
                }
            )
        );
    }

    /**
     * @param array $data
     * @param bool  $logNotification
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendPackageNotifications($data, $logNotification, $notifyCustomers = true, $invoice = null)
    {
        /** @var Collection $customerNotifications */
        $customerNotifications = $this->getByNameAndType(
            "customer_package_" . $data['status'],
            $this->type
        );

        $data['isForCustomer'] = true;

        foreach ($customerNotifications->getItems() as $customerNotification) {
            if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED && $notifyCustomers) {
                $this->sendNotification(
                    $data,
                    $customerNotification,
                    $logNotification,
                    null,
                    null,
                    $invoice
                );
            }
        }

        /** @var Collection $providerNotifications */
        $providerNotifications = $this->getByNameAndType(
            "provider_package_" . $data['status'],
            $this->type
        );

        $data['isForCustomer'] = false;
        foreach ($providerNotifications->getItems() as $providerNotification) {
            if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                $this->sendNotification(
                    $data,
                    $providerNotification,
                    $logNotification
                );
            }
        }
    }

    /**
     * @param array $data
     * @param bool  $logNotification
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function sendCartNotifications($data, $logNotification, $notifyCustomers = true, $invoice = null)
    {
        /** @var Collection $customerNotifications */
        $customerNotifications = $this->getByNameAndType(
            'customer_cart',
            $this->type
        );

        $data['isForCustomer'] = true;

        foreach ($customerNotifications->getItems() as $customerNotification) {
            if ($customerNotification->getStatus()->getValue() === NotificationStatus::ENABLED && $notifyCustomers) {
                $this->sendNotification(
                    $data,
                    $customerNotification,
                    $logNotification,
                    null,
                    null,
                    $invoice
                );
            }
        }

        /** @var Collection $providerNotifications */
        $providerNotifications = $this->getByNameAndType(
            'provider_cart',
            $this->type
        );

        $data['isForCustomer'] = false;

        foreach ($providerNotifications->getItems() as $providerNotification) {
            if ($providerNotification->getStatus()->getValue() === NotificationStatus::ENABLED) {
                $this->sendNotification(
                    $data,
                    $providerNotification,
                    $logNotification
                );
            }
        }
    }

    /**
     * Get User info for notification
     *
     * @param string $userType
     * @param array $entityData
     * @param int $bookingKey
     * @param array $emailData
     *
     * @return array
     * @throws QueryExecutionException
     */
    protected function getUsersInfo($userType, $entityData, $bookingKey, $emailData)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var \AmeliaBooking\Application\Services\Settings\SettingsService $settingsAS*/
        $settingsAS = $this->container->get('application.settings.service');


        $usersInfo = [];

        switch ($userType) {
            case (Entities::CUSTOMER):
                switch ($entityData['type']) {
                    case (Entities::APPOINTMENT):
                    case (Entities::EVENT):
                        if ($bookingKey !== null) {
                            $usersInfo[$entityData['bookings'][$bookingKey]['customerId']] = [
                                'id'    => $entityData['bookings'][$bookingKey]['customerId'],
                                'email' => $emailData['customer_email'],
                                'phone' => $emailData['customer_phone']
                            ];
                        }

                        break;

                    case (Entities::PACKAGE):
                    case (Entities::APPOINTMENTS):
                        $usersInfo[$entityData['customer']['id']] = [
                            'id'    => $entityData['customer']['id'],
                            'email' => $entityData['customer']['email'],
                            'phone' => $entityData['customer']['phone']
                        ];

                        break;
                }


                break;

            case (Entities::PROVIDER):
                switch ($entityData['type']) {
                    case (Entities::APPOINTMENT):
                        $usersInfo[$entityData['providerId']] = [
                            'id'    => $entityData['providerId'],
                            'email' => $emailData['employee_email'],
                            'phone' => $emailData['employee_phone']
                        ];

                        break;

                    case (Entities::EVENT):
                        foreach ((array)$entityData['providers'] as $provider) {
                            $usersInfo[$provider['id']] = [
                                'id'    => $provider['id'],
                                'email' => $provider['email'],
                                'phone' => $provider['phone']
                            ];
                        }
                        if ($entityData['organizerId']) {
                            $organizer = $providerRepository->getById($entityData['organizerId'])->toArray();
                            $usersInfo[$organizer['id']] = [
                                'id'    => $organizer['id'],
                                'email' => $organizer['email'],
                                'phone' => $organizer['phone']
                            ];
                        }

                        break;

                    case (Entities::PACKAGE):
                    case (Entities::APPOINTMENTS):
                        foreach ($entityData['recurring'] as $reservation) {
                            $usersInfo[$reservation['appointment']['provider']['id']] = [
                                'id'    => $reservation['appointment']['provider']['id'],
                                'email' => $reservation['appointment']['provider']['email'],
                                'phone' => $reservation['appointment']['provider']['phone']
                            ];
                        }
                        if (empty($entityData['recurring'])) {
                            if (!empty($entityData['onlyOneEmployee'])) {
                                $usersInfo[$entityData['onlyOneEmployee']['id']] = [
                                    'id' => $entityData['onlyOneEmployee']['id'],
                                    'email' => $entityData['onlyOneEmployee']['email'],
                                    'phone' => $entityData['onlyOneEmployee']['phone']
                                ];
                            }
                            $emptyPackageEmployees = $settingsAS->getEmptyPackageEmployees();
                            if (!empty($emptyPackageEmployees)) {
                                foreach ($emptyPackageEmployees as $employee) {
                                    $usersInfo[$employee['id']] = [
                                        'id'    => $employee['id'],
                                        'email' => $employee['email'],
                                        'phone' => $employee['phone']
                                    ];
                                }
                            }
                        }

                        break;
                }

                break;
        }

        return $usersInfo;
    }
}
