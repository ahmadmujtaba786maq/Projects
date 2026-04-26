<?php

namespace AmeliaBooking\Application\Commands\Google;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetGoogleAuthURLCommand
 *
 * @package AmeliaBooking\Application\Commands\Google
 */
class GetGoogleAuthURLCommand extends Command
{
    /**
     * GetGoogleAuthURLCommand constructor.
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
