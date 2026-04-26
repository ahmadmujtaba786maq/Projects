<?php

namespace AmeliaBooking\Application\Commands\Google;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class DisconnectFromGoogleAccountCommand
 *
 * @package AmeliaBooking\Application\Commands\Google
 */
class DisconnectFromGoogleAccountCommand extends Command
{
    /**
     * DisconnectFromGoogleAccountCommand constructor.
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
