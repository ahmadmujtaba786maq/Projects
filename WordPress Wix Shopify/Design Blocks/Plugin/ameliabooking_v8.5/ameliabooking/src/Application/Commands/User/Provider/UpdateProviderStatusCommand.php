<?php

namespace AmeliaBooking\Application\Commands\User\Provider;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class UpdateProviderStatusCommand
 *
 * @package AmeliaBooking\Application\Commands\User\Provider
 */
class UpdateProviderStatusCommand extends Command
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
