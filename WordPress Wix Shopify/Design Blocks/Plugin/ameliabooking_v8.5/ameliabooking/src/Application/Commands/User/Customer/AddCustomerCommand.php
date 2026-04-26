<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class AddCustomerCommand
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class AddCustomerCommand extends Command
{
    /**
     * AddCustomerCommand constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        parent::__construct($args);
        if (isset($args['type'])) {
            $this->setField('type', $args['type']);
        }
    }
}
