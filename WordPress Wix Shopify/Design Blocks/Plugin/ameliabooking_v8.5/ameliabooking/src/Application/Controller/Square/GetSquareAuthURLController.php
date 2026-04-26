<?php

namespace AmeliaBooking\Application\Controller\Square;

use AmeliaBooking\Application\Commands\Square\GetSquareAuthURLCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetSquareAuthURLController
 *
 * @package AmeliaBooking\Application\Controller\Square
 */
class GetSquareAuthURLController extends Controller
{
    /**
     * Instantiates the Get Outlook Auth URL command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetSquareAuthURLCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetSquareAuthURLCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
