<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\UpdateAppointmentCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateAppointmentController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class UpdateAppointmentController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'bookings',
        'removedBookings',
        'bookingStart',
        'notifyParticipants',
        'createPaymentLinks',
        'internalNotes',
        'serviceId',
        'providerId',
        'locationId',
        'id',
        'payment',
        'utc',
        'timeZone',
        'lessonSpace',
        'createPaymentLinks'
    ];

    /**
     * Instantiates the Update Appointment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateAppointmentCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateAppointmentCommand($args);

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
        if ($result->getResult() === CommandResult::RESULT_SUCCESS) {
            $eventBus->emit('AppointmentEdited', $result);
        }
    }
}
