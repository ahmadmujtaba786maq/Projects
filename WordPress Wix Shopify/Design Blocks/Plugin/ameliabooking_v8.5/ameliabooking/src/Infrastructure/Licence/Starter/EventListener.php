<?php

namespace AmeliaBooking\Infrastructure\Licence\Starter;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Events\DomainEventBus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEventsListener;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentTimeUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingReassignedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventEventsListener;
use AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\ThriveAutomatorService;
use Interop\Container\Exception\ContainerException;
use League\Event\EventInterface;

/**
 * Class EventListener
 *
 * @package AmeliaBooking\Infrastructure\Licence\Starter
 */
class EventListener extends \AmeliaBooking\Infrastructure\Licence\Lite\EventListener
{
    /**
     * Subscribe WP infrastructure to domain events
     *
     * @param DomainEventBus $eventBus
     * @param Container      $container
     *
     * @return AppointmentEventsListener
     */
    public static function subscribeAppointmentListeners($eventBus, $container)
    {
        $appointmentListener = parent::subscribeAppointmentListeners($eventBus, $container);

        $eventBus->addListener('AppointmentEdited', $appointmentListener);
        $eventBus->addListener('BookingTimeUpdated', $appointmentListener);
        $eventBus->addListener('BookingReassigned', $appointmentListener);

        return $appointmentListener;
    }

    /**
     * Subscribe WP infrastructure to domain events
     *
     * @param DomainEventBus $eventBus
     * @param Container      $container
     *
     * @return EventEventsListener
     */
    public static function subscribeEventListeners($eventBus, $container)
    {
        $eventListener = parent::subscribeEventListeners($eventBus, $container);

        $eventBus->addListener('EventEdited', $eventListener);
        $eventBus->addListener('EventAdded', $eventListener);

        return $eventListener;
    }

    /**
     * @param Container          $container
     * @param EventInterface     $event
     * @param CommandResult|null $param
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public static function handleAppointmentListeners(Container $container, EventInterface $event, $param = null)
    {
        ThriveAutomatorService::initItems();

        parent::handleAppointmentListeners($container, $event, $param);

        switch ($event->getName()) {
            case 'AppointmentEdited':
                AppointmentEditedEventHandler::handle($param, $container);
                break;
            case 'BookingTimeUpdated':
                AppointmentTimeUpdatedEventHandler::handle($param, $container);
                break;
            case 'BookingReassigned':
                BookingReassignedEventHandler::handle($param, $container);
                break;
        }
    }

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
        ThriveAutomatorService::initItems();

        parent::handleEventListeners($container, $event, $param);

        switch ($event->getName()) {
            case 'EventEdited':
                EventEditedEventHandler::handle($param, $container);
                break;
        }
    }
}
