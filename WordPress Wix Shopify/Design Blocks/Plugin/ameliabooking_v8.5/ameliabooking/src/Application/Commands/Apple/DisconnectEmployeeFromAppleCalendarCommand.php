<?php

namespace AmeliaBooking\Application\Commands\Apple;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class DisconnectEmployeeFromAppleCalendarCommand
 *
 * @package AmeliaBooking\Application\Commands\Apple
 */
class DisconnectEmployeeFromAppleCalendarCommand extends Command
{
    /**
     * DisconnectEmployeeFromAppleCalendarCommand constructor.
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
