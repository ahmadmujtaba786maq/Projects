<?php

/**
 * Handle WP part of appointment-related events
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Licence\EventListener;
use League\Event\ListenerInterface;
use League\Event\EventInterface;

/**
 * Class AppointmentEventsListener
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment
 */
class AppointmentEventsListener implements ListenerInterface
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
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     * @throws \Exception
     */
    public function handle(EventInterface $event, $param = null)
    {
        // Handling the events
        if ($param->getResult() !== CommandResult::RESULT_ERROR && $param->getResult() !== CommandResult::RESULT_CONFLICT) {
            EventListener::handleAppointmentListeners($this->container, $event, $param);
        }
    }
}
