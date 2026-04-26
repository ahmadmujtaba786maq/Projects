<?php

namespace AmeliaBooking\Application\Controller\Location;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Location\UpdateLocationStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateLocationStatusController
 *
 * @package AmeliaBooking\Application\Controller\Location
 */
class UpdateLocationStatusController extends Controller
{
    /**
     * Fields for location that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Location Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateLocationStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateLocationStatusCommand($args);
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
        $eventBus->emit('location.updated', $result);
    }
}
