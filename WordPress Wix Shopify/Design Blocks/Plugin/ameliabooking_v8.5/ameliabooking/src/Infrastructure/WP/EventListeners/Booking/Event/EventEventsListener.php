<?php

/**
 * Handle WP part of appointment-related events
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Licence\EventListener;
use League\Event\ListenerInterface;
use League\Event\EventInterface;

/**
 * Class EventEventsListener
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event
 */
class EventEventsListener implements ListenerInterface
{
    /** @var Container */
    private $container;

    /**
     * AppointmentEventsListener constructor.
     *
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Check if provided argument is the listener
     *
     * @param mixed $listener
     *
     * @return bool
     */
    public function isListener($listener)
    {
        return $listener === $this;
    }

    /**
     * @param EventInterface     $event
     * @param CommandResult|null $param
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(EventInterface $event, $param = null)
    {
        // Handling the events
        if ($param->getResult() !== CommandResult::RESULT_ERROR && $param->getResult() !== CommandResult::RESULT_CONFLICT) {
            EventListener::handleEventListeners($this->container, $event, $param);
        }
    }
}
