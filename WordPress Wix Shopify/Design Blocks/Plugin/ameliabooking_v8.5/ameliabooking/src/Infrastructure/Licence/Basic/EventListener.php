<?php

namespace AmeliaBooking\Infrastructure\Licence\Basic;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\ThriveAutomatorService;
use Interop\Container\Exception\ContainerException;
use League\Event\EventInterface;

/**
 * Class EventListener
 *
 * @package AmeliaBooking\Infrastructure\Licence\Basic
 */
class EventListener extends \AmeliaBooking\Infrastructure\Licence\Starter\EventListener
{
    /**
     * @param Container          $container
     * @param EventInterface     $event
     * @param CommandResult|null $param
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public static function handleEventListeners(Container $container, EventInterface $event, $param = null)
    {
        parent::handleEventListeners($container, $event, $param);

        switch ($event->getName()) {
            case 'EventAdded':
                EventAddedEventHandler::handle($param, $container);
                break;
        }
    }
}
