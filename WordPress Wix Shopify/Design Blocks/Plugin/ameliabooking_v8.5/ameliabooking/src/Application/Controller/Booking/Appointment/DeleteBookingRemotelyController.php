<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\DeleteBookingRemotelyCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class DeleteBookingRemotelyController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class DeleteBookingRemotelyController extends Controller
{
    /**
     * Fields for delete booking command that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'skipEventHandler',
        'type',
        'token',
    ];

    /**
     * Instantiates the Delete Booking command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteBookingRemotelyCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DeleteBookingRemotelyCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        $command->setField('token', (string)$requestBody['token']);

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
    }
}
