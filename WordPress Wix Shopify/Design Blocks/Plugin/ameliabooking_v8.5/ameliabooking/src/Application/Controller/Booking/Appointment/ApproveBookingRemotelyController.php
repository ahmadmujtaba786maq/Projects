<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\ApproveBookingRemotelyCommand;
use AmeliaBooking\Application\Commands\Booking\Appointment\UpdateBookingStatusCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class ApproveBookingRemotelyController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class ApproveBookingRemotelyController extends Controller
{
    /**
     * Fields for calendar service that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'token'
    ];

    /**
     * Instantiates the Update Appointment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return ApproveBookingRemotelyCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new ApproveBookingRemotelyCommand($args);
        $requestBody = $request->getParsedBody();
        $command->setField('token', (string)$request->getQueryParam('token', ''));
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
        $eventBus->emit('BookingApproved', $result);
    }
}
