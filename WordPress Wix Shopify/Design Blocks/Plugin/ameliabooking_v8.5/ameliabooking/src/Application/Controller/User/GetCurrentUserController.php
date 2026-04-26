<?php

namespace AmeliaBooking\Application\Controller\User;

use AmeliaBooking\Application\Commands\User\GetCurrentUserCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetCurrentUserController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class GetCurrentUserController extends Controller
{
    /**
     * Instantiates the Get Current User command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetCurrentUserCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetCurrentUserCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
