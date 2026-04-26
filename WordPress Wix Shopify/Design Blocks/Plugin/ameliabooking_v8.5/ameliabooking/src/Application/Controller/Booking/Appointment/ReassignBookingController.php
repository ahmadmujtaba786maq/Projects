<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\ReassignBookingCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class ReassignBookingController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class ReassignBookingController extends Controller
{
    /**
     * Fields for booking that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'bookingStart',
        'utcOffset',
        'timeZone',
        'status',
        'serviceId',
        'providerId',
        'locationId',
        'notifyParticipants',
        'customFields',
        'internalNotes',
    ];

    /**
     * Instantiates the Reassign Booking command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return ReassignBookingCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new ReassignBookingCommand($args);

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
        $eventBus->emit('BookingReassigned', $result);
    }
}
