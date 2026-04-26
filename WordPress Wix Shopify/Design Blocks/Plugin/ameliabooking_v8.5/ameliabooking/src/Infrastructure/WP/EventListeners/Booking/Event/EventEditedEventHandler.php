<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\IcsApplicationService;
use AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\WebHook\AbstractWebHookApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Zoom\ZoomFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class EventEditedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event
 */
class EventEditedEventHandler
{
    /** @var string */
    public const TIME_UPDATED = 'bookingTimeUpdated';

    /** @var string */
    public const EVENT_DELETED = 'eventDeleted';

    /** @var string */
    public const EVENT_ADDED = 'eventAdded';

    /** @var string */
    public const EVENT_PERIOD_DELETED = 'eventPeriodDeleted';

    /** @var string */
    public const EVENT_PERIOD_ADDED = 'eventPeriodAdded';

    /** @var string */
    public const ZOOM_USER_CHANGED = 'zoomUserChanged';
    /** @var string */
    public const ZOOM_LICENCED_USER_CHANGED = 'zoomLicencedUserChanged';

    /** @var string */
    public const PROVIDER_CHANGED = 'providerChanged';

    /**
     * @param CommandResult $commandResult
     * @param Container     $container
     *
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
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

        $eventsData = $commandResult->getData()[Entities::EVENTS];

        /** @var Collection $deletedEvents */
        $deletedEvents = self::getCollection($eventsData['deleted']);

        /** @var Collection $rescheduledEvents */
        $rescheduledEvents = self::getCollection($eventsData['rescheduled']);

        /** @var Collection $addedEvents */
        $addedEvents = self::getCollection($eventsData['added']);

        /** @var Collection $clonedEvents */
        $clonedEvents = self::getCollection($eventsData['cloned']);

        /** @var Event $event */
        foreach ($deletedEvents->getItems() as $event) {
            $eventId = $event->getId()->getValue();

            $eventArray = $event->toArray();

            $applicationIntegrationService->handleEvent(
                $event,
                $event->getPeriods(),
                $eventArray,
                ApplicationIntegrationService::EVENT_DELETED,
                [
                    ApplicationIntegrationService::SKIP_ZOOM_MEETING =>
                        !$clonedEvents->keyExists($eventId) ||
                        $clonedEvents->getItem($eventId)->getStatus()->getValue() !== BookingStatus::APPROVED,
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );
        }

        /** @var Event $event */
        foreach ($addedEvents->getItems() as $event) {
            $eventArray = $event->toArray();

            $applicationIntegrationService->handleEvent(
                $event,
                $event->getPeriods(),
                $eventArray,
                ApplicationIntegrationService::EVENT_ADDED
            );
        }

        /** @var Event $event */
        foreach ($clonedEvents->getItems() as $event) {
            $eventArray = $event->toArray();

            $applicationIntegrationService->handleEvent(
                $event,
                $event->getPeriods(),
                $eventArray,
                '',
                [
                    ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => true,
                    ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => true,
                    ApplicationIntegrationService::SKIP_ZOOM_MEETING     => true,
                    ApplicationIntegrationService::SKIP_APPLE_CALENDAR   => true,
                ]
            );
        }

        /** @var Event $event */
        foreach ($rescheduledEvents->getItems() as $event) {
            $eventId = $event->getId()->getValue();

            /** @var Event $clonedEvent */
            $clonedEvent = $clonedEvents->keyExists($eventId) ? $clonedEvents->getItem($eventId) : null;

            $eventArray = $event->toArray();

            $applicationIntegrationService->handleEvent(
                $event,
                $event->getPeriods(),
                $eventArray,
                '',
                [
                    ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => true,
                    ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => true,
                    ApplicationIntegrationService::SKIP_ZOOM_MEETING     => true,
                    ApplicationIntegrationService::SKIP_APPLE_CALENDAR   => true,
                ]
            );

            if ($clonedEvent && $clonedEvent->getStatus()->getValue() === BookingStatus::APPROVED) {
                /** @var Collection $rescheduledPeriods */
                $rescheduledPeriods = new Collection();

                /** @var Collection $addedPeriods */
                $addedPeriods = new Collection();

                /** @var Collection $deletedPeriods */
                $deletedPeriods = new Collection();

                /** @var EventPeriod $eventPeriod */
                foreach ($event->getPeriods()->getItems() as $eventPeriod) {
                    $eventPeriodId = $eventPeriod->getId()->getValue();

                    /** @var EventPeriod $clonedEventPeriod */
                    $clonedEventPeriod = $clonedEvent->getPeriods()->keyExists($eventPeriodId) ?
                        $clonedEvent->getPeriods()->getItem($eventPeriodId) : null;

                    if ($clonedEventPeriod && $clonedEventPeriod->toArray() !== $eventPeriod->toArray()) {
                        $rescheduledPeriods->addItem($eventPeriod, $eventPeriodId);
                    } elseif (!$clonedEventPeriod) {
                        $addedPeriods->addItem($eventPeriod, $eventPeriodId);
                    }
                }

                /** @var EventPeriod $clonedEventPeriod */
                foreach ($clonedEvent->getPeriods()->getItems() as $clonedEventPeriod) {
                    $eventPeriodId = $clonedEventPeriod->getId()->getValue();
                    if (!$event->getPeriods()->keyExists($eventPeriodId)) {
                        $deletedPeriods->addItem($clonedEventPeriod, $clonedEventPeriod->getId()->getValue());
                    }
                }

                if ($rescheduledPeriods->length()) {
                    $applicationIntegrationService->handleEvent(
                        $event,
                        $rescheduledPeriods,
                        $eventArray,
                        ApplicationIntegrationService::TIME_UPDATED,
                        [
                            ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                        ]
                    );
                }

                if ($addedPeriods->length()) {
                    $applicationIntegrationService->handleEvent(
                        $event,
                        $addedPeriods,
                        $eventArray,
                        ApplicationIntegrationService::EVENT_PERIOD_ADDED,
                        [
                            ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                        ]
                    );
                }

                if ($deletedPeriods->length()) {
                    $applicationIntegrationService->handleEvent(
                        $event,
                        $deletedPeriods,
                        $eventArray,
                        ApplicationIntegrationService::EVENT_PERIOD_DELETED,
                        [
                            ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                        ]
                    );
                }
            }
        }


        $zoomUserChange = $commandResult->getData()['zoomUserChanged'];
        if ($zoomUserChange) {
            if (!$rescheduledEvents->length()) {
                $command = $commandResult->getData()['zoomUsersLicenced'] ? self::ZOOM_LICENCED_USER_CHANGED : self::ZOOM_USER_CHANGED;
                /** @var Event $event */
                foreach ($clonedEvents->getItems() as $event) {
                    $eventArray = $event->toArray();

                    $applicationIntegrationService->handleEvent(
                        $event,
                        $event->getPeriods(),
                        $eventArray,
                        $command,
                        [
                            ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => true,
                            ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => true,
                            ApplicationIntegrationService::SKIP_LESSON_SPACE     => true,
                            ApplicationIntegrationService::SKIP_APPLE_CALENDAR   => true,
                        ],
                        [
                            'zoomUserId' => $zoomUserChange,
                        ]
                    );
                }
            }
        }

        if ($commandResult->getData()['newInfo']) {
            if (!$rescheduledEvents->length()) {
                /** @var Event $event */
                foreach ($clonedEvents->getItems() as $event) {
                    $eventArray = $event->toArray();

                    $applicationIntegrationService->handleEvent(
                        $event,
                        $event->getPeriods(),
                        $eventArray,
                        ApplicationIntegrationService::EVENT_STATUS_UPDATED,
                        [
                            ApplicationIntegrationService::SKIP_GOOGLE_CALENDAR  => true,
                            ApplicationIntegrationService::SKIP_OUTLOOK_CALENDAR => true,
                            ApplicationIntegrationService::SKIP_LESSON_SPACE     => true,
                            ApplicationIntegrationService::SKIP_APPLE_CALENDAR   => true,
                        ]
                    );
                }
            }
        }

        $newProviders    = $commandResult->getData()['newProviders'];
        $removeProviders = $commandResult->getData()['removeProviders'];
        $newInfo         = $commandResult->getData()['newInfo'];
        $organizerChange = $commandResult->getData()['organizerChanged'];
        $newOrganizer    = $commandResult->getData()['newOrganizer'];
        /** @var Event $event */
        foreach ($clonedEvents->getItems() as $event) {
            if ($organizerChange) {
                $eventArray = $event->toArray();

                $applicationIntegrationService->handleEvent(
                    $event,
                    $event->getPeriods(),
                    $eventArray,
                    ApplicationIntegrationService::EVENT_PERIOD_DELETED,
                    [
                        ApplicationIntegrationService::SKIP_ZOOM_MEETING => true,
                        ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                    ]
                );

                if ($newOrganizer) {
                    $event->setOrganizerId(new Id($newOrganizer));

                    $applicationIntegrationService->handleEvent(
                        $event,
                        $event->getPeriods(),
                        $eventArray,
                        ApplicationIntegrationService::EVENT_PERIOD_ADDED,
                        [
                            ApplicationIntegrationService::SKIP_ZOOM_MEETING => true,
                            ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                        ]
                    );
                }
            }
            if ($newInfo) {
                $event->setName($newInfo['name']);
                $event->setDescription($newInfo['description']);
            }
            if (($newProviders || $removeProviders || $newInfo) && (!$organizerChange || $newOrganizer)) {
                $applicationIntegrationService->handleEvent(
                    $event,
                    $event->getPeriods(),
                    $eventArray,
                    ApplicationIntegrationService::PROVIDER_CHANGED,
                    [
                        ApplicationIntegrationService::SKIP_ZOOM_MEETING => true,
                        ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                    ],
                    [
                        'providersNew'    => $newProviders,
                        'providersRemove' => $removeProviders,
                    ]
                );
            }
        }

        if (count($eventsData['edited']) > 0 && !$addedEvents->length() && !$rescheduledEvents->length() && !$deletedEvents->length()) {
            foreach ($clonedEvents->getItems() as $event) {
                foreach ($event->getPeriods()->toArray() as $index => $eventPeriod) {
                    if (!empty($eventsData['edited'][$event->getId()->getValue()])) {
                        /** @var EventPeriod $changedPeriod */
                        $changedPeriod = $eventsData['edited'][$event->getId()->getValue()]->getPeriods()->getItem($index);
                        if ($changedPeriod) {
                            if (
                                !empty($eventPeriod['zoomMeeting']) &&
                                !empty($eventPeriod['zoomMeeting']['id']) &&
                                !empty($eventPeriod['zoomMeeting']['joinUrl']) &&
                                !empty($eventPeriod['zoomMeeting']['startUrl'])
                            ) {
                                $zoomMeeting = ZoomFactory::create(
                                    $eventPeriod['zoomMeeting']
                                );
                                $changedPeriod->setZoomMeeting($zoomMeeting);
                            } else {
                                $changedPeriod->setZoomMeeting(ZoomFactory::create([]));
                            }
                            $changedPeriod->setGoogleMeetUrl($eventPeriod['googleMeetUrl']);

                            $changedPeriod->setMicrosoftTeamsUrl($eventPeriod['microsoftTeamsUrl']);
                        }
                    }
                }
            }

            foreach ($eventsData['edited'] as $event) {
                $eventArray = $event->toArray();
                foreach ($eventArray['bookings'] as $index => $booking) {
                    $paymentId   = $booking['payments'][0]['id'];
                    $paymentData = [
                        'booking' => $booking,
                        'type' => Entities::EVENT,
                        'event' => $eventArray,
                        'paymentId' => $paymentId,
                        'bookable' => $eventArray,
                        'customer' => $booking['customer']
                    ];
                    if (!empty($paymentId)) {
                        $eventArray['bookings'][$index]['payments'][0]['paymentLinks'] = $paymentAS->createPaymentLink($paymentData, $index);
                    }
                }

                $emailNotificationService->sendAppointmentUpdatedNotifications($eventArray);

                if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                    $smsNotificationService->sendAppointmentUpdatedNotifications($eventArray);
                }

                if ($whatsAppNotificationService->checkRequiredFields()) {
                    $whatsAppNotificationService->sendAppointmentUpdatedNotifications($eventArray);
                }
            }
        }

        foreach ($eventsData['rescheduled'] as $eventArray) {
            foreach ($eventArray['bookings'] as $index => $booking) {
                $paymentId   = $booking['payments'][0]['id'];
                $paymentData = [
                    'booking' => $booking,
                    'type' => Entities::EVENT,
                    'event' => $eventArray,
                    'paymentId' => $paymentId,
                    'bookable' => $eventArray,
                    'customer' => $booking['customer']
                ];
                if (!empty($paymentId)) {
                    $eventArray['bookings'][$index]['payments'][0]['paymentLinks'] = $paymentAS->createPaymentLink($paymentData, $index);
                }
            }

            /** @var IcsApplicationService $icsService */
            $icsService = $container->get('application.ics.service');

            foreach ($eventArray['bookings'] as $index => $booking) {
                if ($booking['status'] === BookingStatus::APPROVED || $booking['status'] === BookingStatus::PENDING) {
                    $eventArray['bookings'][$index]['icsFiles'] = $icsService->getIcsData(
                        Entities::EVENT,
                        $booking['id'],
                        [],
                        true
                    );
                }
            }

            $emailNotificationService->sendAppointmentRescheduleNotifications($eventArray);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendAppointmentRescheduleNotifications($eventArray);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendAppointmentRescheduleNotifications($eventArray);
            }

            $webHookService->process(self::TIME_UPDATED, $eventArray, []);
        }
    }

    /**
     * @param array $eventsArray
     *
     * @return Collection
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    private static function getCollection($eventsArray)
    {
        /** @var Collection $events */
        $events = new Collection();

        foreach ($eventsArray as $eventArray) {
            /** @var Event $eventObject */
            $eventObject = EventFactory::create($eventArray);

            /** @var Collection $eventPeriods */
            $eventPeriods = new Collection();

            /** @var EventPeriod $period */
            foreach ($eventObject->getPeriods()->getItems() as $period) {
                $eventPeriods->addItem($period, $period->getId()->getValue());
            }

            $eventObject->setPeriods($eventPeriods);

            $events->addItem($eventObject, $eventObject->getId()->getValue());
        }
        return $events;
    }
}
