<?php

namespace AmeliaBooking\Infrastructure\Licence\Lite;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Events\DomainEventBus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentDeletedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEventsListener;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentStatusUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingApprovedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingCanceledEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingRejectedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\BookingStatusUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventEventsListener;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Event\EventStatusUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\User\Provider\ProviderAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\User\Provider\ProviderUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\User\UserAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\User\UserEventsListener;
use Interop\Container\Exception\ContainerException;
use League\Event\EventInterface;

/**
 * Class EventListener
 *
 * @package AmeliaBooking\Infrastructure\Licence\Lite
 */
class EventListener
{
    /**
     * Subscribe WP infrastructure to domain events
     *
     * @param DomainEventBus $eventBus
     * @param Container      $container
     *
     * @return UserEventsListener
     */
    public static function subscribeUserListeners($eventBus, $container)
    {
        $userListener = new UserEventsListener($container);

        $eventBus->addListener('user.added', $userListener);
        $eventBus->addListener('user.deleted', $userListener);
        $eventBus->addListener('provider.updated', $userListener);
        $eventBus->addListener('provider.added', $userListener);

        return $userListener;
    }

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
        $appointmentListener = new AppointmentEventsListener($container);

        $eventBus->addListener('AppointmentAdded', $appointmentListener);
        $eventBus->addListener('AppointmentDeleted', $appointmentListener);
        $eventBus->addListener('AppointmentStatusUpdated', $appointmentListener);
        $eventBus->addListener('BookingAdded', $appointmentListener);
        $eventBus->addListener('BookingStatusUpdated', $appointmentListener);
        $eventBus->addListener('BookingCanceled', $appointmentListener);
        $eventBus->addListener('BookingApproved', $appointmentListener);
        $eventBus->addListener('BookingRejected', $appointmentListener);
        $eventBus->addListener('BookingDeleted', $appointmentListener);

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
        $eventListener = new EventEventsListener($container);

        $eventBus->addListener('EventStatusUpdated', $eventListener);
        $eventBus->addListener('EventBookingEdited', $eventListener);

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
        switch ($event->getName()) {
            case 'AppointmentAdded':
                do_action('AmeliaAppointmentAddedBeforeNotify', $param->getData(), $container);
                AppointmentAddedEventHandler::handle($param, $container);
                break;
            case 'AppointmentDeleted':
                AppointmentDeletedEventHandler::handle($param, $container);
                break;
            case 'AppointmentStatusUpdated':
                AppointmentStatusUpdatedEventHandler::handle($param, $container);
                break;
            case 'BookingAdded':
                do_action('AmeliaBookingAddedBeforeNotify', $param->getData(), $container);
                BookingAddedEventHandler::handle($param, $container);
                break;
            case 'BookingStatusUpdated':
                BookingStatusUpdatedEventHandler::handle($param, $container);
                break;
            case 'BookingCanceled':
                BookingCanceledEventHandler::handle($param, $container);
                break;
            case 'BookingApproved':
                BookingApprovedEventHandler::handle($param, $container);
                break;
            case 'BookingRejected':
                BookingRejectedEventHandler::handle($param, $container);
                break;
            case 'BookingDeleted':
                if ($param->getData()['appointmentDeleted']) {
                    AppointmentDeletedEventHandler::handle($param, $container);
                } elseif ($param->getData()['bookingDeleted']) {
                    AppointmentEditedEventHandler::handle($param, $container);
                }
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
        switch ($event->getName()) {
            case 'EventStatusUpdated':
                EventStatusUpdatedEventHandler::handle($param, $container);
                break;
            case 'EventBookingEdited':
                BookingEditedEventHandler::handle($param, $container);
                break;
        }
    }

    /**
     * @param Container          $container
     * @param EventInterface     $event
     * @param CommandResult|null $param
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public static function handleUserListeners(Container $container, EventInterface $event, $param = null)
    {
        switch ($event->getName()) {
            case 'user.added':
                UserAddedEventHandler::handle($param);
                break;
            case 'provider.updated':
                ProviderUpdatedEventHandler::handle($param, $container);
                break;
            case 'provider.added':
                ProviderAddedEventHandler::handle($param, $container);
                break;
        }
    }
}
