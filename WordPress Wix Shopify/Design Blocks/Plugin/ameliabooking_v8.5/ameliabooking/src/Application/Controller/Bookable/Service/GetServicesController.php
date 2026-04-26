<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\GetServicesCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetServicesController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class GetServicesController extends Controller
{
    /**
     * Instantiates the Get Services command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetServicesCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetServicesCommand($args);

        $params = (array)$request->getQueryParams();

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
