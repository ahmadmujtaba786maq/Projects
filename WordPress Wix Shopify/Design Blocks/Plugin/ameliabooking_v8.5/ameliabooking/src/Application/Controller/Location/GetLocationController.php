<?php

namespace AmeliaBooking\Application\Controller\Location;

use AmeliaBooking\Application\Commands\Location\GetLocationCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetLocationController
 *
 * @package AmeliaBooking\Application\Controller\Location
 */
class GetLocationController extends Controller
{
    /**
     * Instantiates the Get Location command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetLocationCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetLocationCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
