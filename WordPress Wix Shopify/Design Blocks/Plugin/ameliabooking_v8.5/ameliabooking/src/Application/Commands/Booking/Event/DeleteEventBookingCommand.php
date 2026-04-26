<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class DeleteEventBookingCommand
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class DeleteEventBookingCommand extends Command
{
    /**
     * GetEventCommand constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        parent::__construct($args);
        if (isset($args['id'])) {
            $this->setField('id', $args['id']);
        }
    }
}
