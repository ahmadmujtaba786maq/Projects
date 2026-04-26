<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetEventCommand
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventCommand extends Command
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
