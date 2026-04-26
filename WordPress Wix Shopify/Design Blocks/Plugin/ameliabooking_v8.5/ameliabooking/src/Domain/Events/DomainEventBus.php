<?php

namespace AmeliaBooking\Domain\Events;

use League\Event\Emitter;

/**
 * Wrapper for the League Event library to keep the domain independent of infrastructure
 * Class DomainEventBus
 *
 * @package AmeliaBooking\Domain\Events
 */
class DomainEventBus
{
    /**
     * Implementation of event emitter
     *
     * @var Emitter $eventEmitter
     */
    private $eventEmitter;

    /**
     * Constructor with injection of event emitter implementation
     *
     * @param Emitter $eventEmitter
     */
    public function __construct($eventEmitter)
    {
        $this->eventEmitter = $eventEmitter;
    }

    /**
     * Emitting the event through the Emitter
     *
     * @param $eventName
     * @param $eventParams
     */
    public function emit($eventName, $eventParams)
    {
        $this->eventEmitter->emit($eventName, $eventParams);
    }

    /**
     * Adding an event listener
     *
     * @param $eventName
     * @param $subscriber
     */
    public function addListener($eventName, $subscriber)
    {
        $this->eventEmitter->addListener($eventName, $subscriber);
    }
}
