<?php

namespace AmeliaBooking\Application\Commands\User\Provider;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class AddProviderCommand
 *
 * @package AmeliaBooking\Application\Commands\User\Provider
 */
class AddProviderCommand extends Command
{
    /**
     * AddProviderCommand constructor.
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
