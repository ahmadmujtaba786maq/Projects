<?php

namespace AmeliaBooking\Application\Commands\Outlook;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class DisconnectFromOutlookAccountCommand
 *
 * @package AmeliaBooking\Application\Commands\Outlook
 */
class DisconnectFromOutlookAccountCommand extends Command
{
    /**
     * DisconnectFromOutlookAccountCommand constructor.
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
