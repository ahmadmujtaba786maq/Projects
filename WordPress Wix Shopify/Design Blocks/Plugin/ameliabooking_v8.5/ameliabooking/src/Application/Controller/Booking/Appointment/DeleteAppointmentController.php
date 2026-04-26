<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\DeleteAppointmentCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class DeleteAppointmentController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class DeleteAppointmentController extends Controller
{
    /**
     * Instantiates the Delete Appointment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteAppointmentCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('AppointmentDeleted', $result);
    }
}
