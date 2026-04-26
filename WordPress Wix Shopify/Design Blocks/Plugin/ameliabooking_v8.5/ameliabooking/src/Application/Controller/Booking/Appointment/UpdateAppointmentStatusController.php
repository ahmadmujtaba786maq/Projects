<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\UpdateAppointmentStatusCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateAppointmentStatusController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class UpdateAppointmentStatusController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Appointment Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateAppointmentStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateAppointmentStatusCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        $params = $request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
        }

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
        $eventBus->emit('AppointmentStatusUpdated', $result);
    }
}
