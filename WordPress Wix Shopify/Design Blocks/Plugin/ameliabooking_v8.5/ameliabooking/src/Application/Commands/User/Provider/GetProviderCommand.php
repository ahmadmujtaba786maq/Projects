<?php

namespace AmeliaBooking\Application\Commands\User\Provider;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetProviderCommand
 *
 * @package AmeliaBooking\Application\Commands\User\Provider
 */
class GetProviderCommand extends Command
{
    /**
     * GetProviderCommand constructor.
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
