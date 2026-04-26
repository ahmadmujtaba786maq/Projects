<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Integration\ApplicationIntegrationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class EventAddedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event
 */
class EventAddedEventHandler
{
    /** @var string */
    public const EVENT_ADDED = 'eventAdded';

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
                ApplicationIntegrationService::EVENT_ADDED
            );
        }
    }
}
