<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Container;
use Microsoft\Graph\Exception\GraphException;

/**
 * Class EventStatusUpdatedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event
 */
class EventStatusUpdatedEventHandler
{
    /** @var string */
    public const EVENT_STATUS_UPDATED = 'eventStatusUpdated';

    /**
     * @param CommandResult $commandResult
     * @param Container $container
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
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
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $container->get('application.booking.booking.service');

        $events = $commandResult->getData()[Entities::EVENTS];

        foreach ($events as $event) {
            /** @var Event $reservationObject */
            $reservationObject = EventFactory::create($event);

            $bookingApplicationService->setReservationEntities($reservationObject);

            $applicationIntegrationService->handleEvent(
                $reservationObject,
                $reservationObject->getPeriods(),
                $event,
                ApplicationIntegrationService::EVENT_STATUS_UPDATED,
                [
                    ApplicationIntegrationService::SKIP_LESSON_SPACE => true,
                ]
            );

            $emailNotificationService->sendAppointmentStatusNotifications($event, false, true);

            if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
                $smsNotificationService->sendAppointmentStatusNotifications($event, false, true);
            }

            if ($whatsAppNotificationService->checkRequiredFields()) {
                $whatsAppNotificationService->sendAppointmentStatusNotifications($event, false, true);
            }
        }
    }
}
