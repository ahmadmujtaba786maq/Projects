<?php

defined('ABSPATH') or die('No script kiddies please!');

use AmeliaBooking\Infrastructure\Common\Container;
use League\Event\Emitter;
use AmeliaBooking\Domain\Events\DomainEventBus;
use AmeliaBooking\Infrastructure\WP\EventListeners\EventSubscribers;

/**
 * @param Container $c
 *
 * @return DomainEventBus
 */
$entries['domain.event.bus'] = function ($c) {
    $eventBus = new DomainEventBus(new Emitter());
    // Subscribe the WP event listeners
    EventSubscribers::subscribe($eventBus, $c);
    return $eventBus;
};
