<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetIcsCommand
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetIcsCommand extends Command
{
    /**
     * GetIcsCommand constructor.
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
