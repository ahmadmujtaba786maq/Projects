<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetCustomerCommand
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class GetCustomerCommand extends Command
{
    /**
     * AddCustomerCommand constructor.
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
