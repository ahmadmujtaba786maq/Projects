<?php

namespace AmeliaBooking\Application\Commands\Square;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class DisconnectFromSquareAccountCommand
 *
 * @package AmeliaBooking\Application\Commands\Square
 */
class DisconnectFromSquareAccountCommand extends Command
{
    /**
     * DisconnectFromSquareAccountCommand constructor.
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
