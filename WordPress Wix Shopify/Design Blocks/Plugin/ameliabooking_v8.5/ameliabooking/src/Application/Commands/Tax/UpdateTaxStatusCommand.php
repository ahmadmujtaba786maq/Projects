<?php

namespace AmeliaBooking\Application\Commands\Tax;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class UpdateTaxStatusCommand
 *
 * @package AmeliaBooking\Application\Commands\Tax
 */
class UpdateTaxStatusCommand extends Command
{
    /**
     * UpdateTaxStatusCommand constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        parent::__construct($args);
    }
}
