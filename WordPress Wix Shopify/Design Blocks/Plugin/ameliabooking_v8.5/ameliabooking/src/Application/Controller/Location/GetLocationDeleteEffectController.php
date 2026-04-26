<?php

namespace AmeliaBooking\Application\Controller\Location;

use AmeliaBooking\Application\Commands\Location\GetLocationDeleteEffectCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetLocationDeleteEffectController
 *
 * @package AmeliaBooking\Application\Controller\Location
 */
class GetLocationDeleteEffectController extends Controller
{
    /**
     * Instantiates the Get Location command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetLocationDeleteEffectCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetLocationDeleteEffectCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
