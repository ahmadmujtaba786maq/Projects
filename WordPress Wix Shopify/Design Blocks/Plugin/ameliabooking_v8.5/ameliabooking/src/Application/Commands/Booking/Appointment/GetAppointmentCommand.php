<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class GetAppointmentCommand
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetAppointmentCommand extends Command
{
    /**
     * GetAppointmentCommand constructor.
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
