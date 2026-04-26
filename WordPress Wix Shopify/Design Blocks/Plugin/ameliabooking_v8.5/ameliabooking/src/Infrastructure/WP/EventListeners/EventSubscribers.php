<?php

/**
 * Subscribe to domain events
 */

namespace AmeliaBooking\Infrastructure\WP\EventListeners;

use AmeliaBooking\Domain\Events\DomainEventBus;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Licence\EventListener;

/**
 * Class EventSubscribers
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners
 */
class EventSubscribers
{
    /**
     * Subscribe WP infrastructure to domain events
     *
     * @param DomainEventBus $eventBus
     * @param Container      $container
     */
    public static function subscribe($eventBus, $container)
    {
        EventListener::subscribeUserListeners($eventBus, $container);
        EventListener::subscribeAppointmentListeners($eventBus, $container);
        EventListener::subscribeEventListeners($eventBus, $container);
    }
}
