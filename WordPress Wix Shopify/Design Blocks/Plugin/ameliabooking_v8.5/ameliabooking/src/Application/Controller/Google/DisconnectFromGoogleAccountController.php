<?php

namespace AmeliaBooking\Application\Controller\Google;

use AmeliaBooking\Application\Commands\Google\DisconnectFromGoogleAccountCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DisconnectFromGoogleAccountController
 *
 * @package AmeliaBooking\Application\Controller\Google
 */
class DisconnectFromGoogleAccountController extends Controller
{
    /**
     * @param Request $request
     * @param         $args
     *
     * @return DisconnectFromGoogleAccountCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DisconnectFromGoogleAccountCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        return $command;
    }
}
