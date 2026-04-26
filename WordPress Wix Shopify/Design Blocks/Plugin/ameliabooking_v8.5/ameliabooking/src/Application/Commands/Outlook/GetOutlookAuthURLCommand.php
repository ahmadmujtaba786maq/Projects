<?php

namespace AmeliaBooking\Application\Commands\Outlook;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetOutlookAuthURLCommand
 *
 * @package AmeliaBooking\Application\Commands\Outlook
 */
class GetOutlookAuthURLCommand extends Command
{
    /**
     * GetOutlookAuthURLCommand constructor.
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
