<?php

namespace AmeliaBooking\Application\Controller\Location;

use AmeliaBooking\Application\Commands\Location\GetLocationsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetLocationsController
 *
 * @package AmeliaBooking\Application\Controller\Location
 */
class GetLocationsController extends Controller
{
    /**
     * Instantiates the Get Locations command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetLocationsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetLocationsCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['services'])) {
            $params['services'] = array_map('intval', $params['services']);
        }

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
