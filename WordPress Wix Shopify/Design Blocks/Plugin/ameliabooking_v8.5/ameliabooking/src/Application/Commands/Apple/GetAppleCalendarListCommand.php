<?php

namespace AmeliaBooking\Application\Commands\Apple;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetAppleCalendarListCommand
 *
 * @package AmeliaBooking\Application\Commands\Apple
 */
class GetAppleCalendarListCommand extends Command
{
    /**
     * GetAppleCalendarListCommand constructor.
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
