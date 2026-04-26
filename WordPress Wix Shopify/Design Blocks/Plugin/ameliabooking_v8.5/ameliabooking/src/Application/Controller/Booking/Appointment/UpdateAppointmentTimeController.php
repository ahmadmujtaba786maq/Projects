<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\UpdateAppointmentTimeCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateAppointmentTimeController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class UpdateAppointmentTimeController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'bookingStart',
        'timeZone',
        'utcOffset'
    ];

    /**
     * Instantiates the Update Appointment Time command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateAppointmentTimeCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateAppointmentTimeCommand($args);
        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        $params = (array)$request->getQueryParams();

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
        $eventBus->emit('BookingTimeUpdated', $result);
    }
}
