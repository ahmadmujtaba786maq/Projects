<?php

namespace AmeliaBooking\Application\Controller\User;

use AmeliaBooking\Application\Commands\User\DeleteUserCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class DeleteUserController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class DeleteUserController extends Controller
{
    /**
     * Instantiates the Delete User command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteUserCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteUserCommand($args);
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
        $eventBus->emit('user.deleted', $result);
    }
}
