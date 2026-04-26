<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class UpdateCustomerStatusCommand
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class UpdateCustomerStatusCommand extends Command
{
    /**
     * UpdateProviderStatusCommand constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        parent::__construct($args);
    }
}
