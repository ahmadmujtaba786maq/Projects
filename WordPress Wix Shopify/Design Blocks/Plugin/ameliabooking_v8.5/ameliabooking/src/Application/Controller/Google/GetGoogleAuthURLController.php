<?php

namespace AmeliaBooking\Application\Controller\Google;

use AmeliaBooking\Application\Commands\Google\GetGoogleAuthURLCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetGoogleAuthURLController
 *
 * @package AmeliaBooking\Application\Controller\Google
 */
class GetGoogleAuthURLController extends Controller
{
    /**
     * Instantiates the Get Google Auth URL command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetGoogleAuthURLCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetGoogleAuthURLCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
