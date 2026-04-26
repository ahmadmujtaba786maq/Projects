<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\UpdateEventBookingCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateEventBookingController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class UpdateEventBookingController extends Controller
{
    /**
     * Fields for Booking that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'type',
        'bookings',
        'createPaymentLinks'
    ];

    /**
     * Instantiates the Update Booking command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateEventBookingCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateEventBookingCommand($args);

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
        $eventBus->emit('EventBookingEdited', $result);
    }
}
