<?php

namespace AmeliaBooking\Application\Controller\User;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\User\LogoutCabinetCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class LogoutCabinetController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class LogoutCabinetController extends Controller
{
    /**
     * Instantiates the Logout Cabinet command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return LogoutCabinetCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new LogoutCabinetCommand($args);

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
    }
}
