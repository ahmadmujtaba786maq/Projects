<?php

namespace AmeliaBooking\Infrastructure\Licence\Pro;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Events\DomainEventBus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEditedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentEventsListener;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\PackageCustomerAddedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\PackageCustomerDeletedEventHandler;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\PackageCustomerUpdatedEventHandler;
use Interop\Container\Exception\ContainerException;
use League\Event\EventInterface;

/**
 * Class EventListener
 *
 * @package AmeliaBooking\Infrastructure\Licence\Pro
 */
class EventListener extends \AmeliaBooking\Infrastructure\Licence\Basic\EventListener
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

        $eventBus->addListener('PackageCustomerUpdated', $appointmentListener);
        $eventBus->addListener('PackageCustomerAdded', $appointmentListener);
        $eventBus->addListener('PackageCustomerDeleted', $appointmentListener);

        return $appointmentListener;
    }

    /**
     * @param Container                 $container
     * @param EventInterface            $event
     * @param CommandResult|null        $param
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public static function handleAppointmentListeners(Container $container, EventInterface $event, $param = null)
    {
        parent::handleAppointmentListeners($container, $event, $param);

        switch ($event->getName()) {
            case 'PackageCustomerUpdated':
                PackageCustomerUpdatedEventHandler::handle($param, $container);
                break;
            case 'PackageCustomerAdded':
                PackageCustomerAddedEventHandler::handle($param, $container);
                break;
            case 'PackageCustomerDeleted':
                $appointmentUpdatedResult = new CommandResult();

                foreach ($param->getData()['appointments']['updatedAppointments'] as $item) {
                    $appointmentUpdatedResult->setData($item);

                    AppointmentEditedEventHandler::handle($appointmentUpdatedResult, $container);
                }

                PackageCustomerDeletedEventHandler::handle($param, $container);

                break;
        }
    }
}
