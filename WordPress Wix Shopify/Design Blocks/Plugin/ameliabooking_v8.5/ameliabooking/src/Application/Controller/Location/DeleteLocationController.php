<?php

namespace AmeliaBooking\Application\Controller\Location;

use AmeliaBooking\Application\Commands\Location\DeleteLocationCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class DeleteLocationController
 *
 * @package AmeliaBooking\Application\Controller\Location
 */
class DeleteLocationController extends Controller
{
    /**
     * Instantiates the Delete Location command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteLocationCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteLocationCommand($args);
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
        $eventBus->emit('location.deleted', $result);
    }
}
