<?php

namespace AmeliaBooking\Application\Controller\User;

use AmeliaBooking\Application\Commands\User\GetUserDeleteEffectCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetUserDeleteEffectController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class GetUserDeleteEffectController extends Controller
{
    /**
     * Instantiates the Get User Delete Effect command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetUserDeleteEffectCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetUserDeleteEffectCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
