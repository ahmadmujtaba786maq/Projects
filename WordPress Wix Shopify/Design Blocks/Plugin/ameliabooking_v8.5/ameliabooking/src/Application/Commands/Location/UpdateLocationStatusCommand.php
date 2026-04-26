<?php

namespace AmeliaBooking\Application\Commands\Location;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class UpdateLocationStatusCommand
 *
 * @package AmeliaBooking\Application\Commands\Location
 */
class UpdateLocationStatusCommand extends Command
{
    /**
     * UpdateLocationStatusCommand constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        parent::__construct($args);
    }
}
