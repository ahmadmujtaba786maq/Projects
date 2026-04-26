<?php

namespace AmeliaBooking\Application\Controller\User;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\User\LoginCabinetCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class LoginCabinetController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class LoginCabinetController extends Controller
{
    /**
     * Fields for login that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'email',
        'password',
        'token',
        'checkIfWpUser',
        'cabinetType',
        'changePass',
        'recaptcha',
    ];

    /**
     * Instantiates the Login Cabinet command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return LoginCabinetCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new LoginCabinetCommand($args);

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
    }
}
