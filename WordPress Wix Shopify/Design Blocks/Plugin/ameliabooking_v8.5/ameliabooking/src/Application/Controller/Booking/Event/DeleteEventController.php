<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\DeleteEventCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class DeleteEventController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class DeleteEventController extends Controller
{
    /**
     * Fields for Event that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'applyGlobally'
    ];

    /**
     * Instantiates the Delete Event command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteEventCommand($args);
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
        $eventBus->emit('EventDeleted', $result);
    }
}
