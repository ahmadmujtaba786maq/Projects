<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\DeleteEventBookingCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class DeleteEventBookingController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class DeleteEventBookingController extends Controller
{
    /**
     * Instantiates the Delete Event Booking command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteEventBookingCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DeleteEventBookingCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

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
        $eventBus->emit('BookingCanceled', $result);
    }
}
