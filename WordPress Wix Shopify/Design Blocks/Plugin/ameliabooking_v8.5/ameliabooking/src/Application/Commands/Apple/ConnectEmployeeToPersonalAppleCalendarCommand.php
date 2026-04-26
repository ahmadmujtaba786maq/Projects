<?php

namespace AmeliaBooking\Application\Commands\Apple;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class ConnectEmployeeToPersonalAppleCalendarCommand
 *
 * @package AmeliaBooking\Application\Commands\Apple
 */
class ConnectEmployeeToPersonalAppleCalendarCommand extends Command
{
    /**
     * ConnectEmployeeToPersonalAppleCalendarCommand constructor.
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
