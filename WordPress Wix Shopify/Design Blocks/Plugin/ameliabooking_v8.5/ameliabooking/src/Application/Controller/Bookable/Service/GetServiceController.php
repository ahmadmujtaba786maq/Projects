<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\GetServiceCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetServiceController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class GetServiceController extends Controller
{
    /**
     * Instantiates the Get Service command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetServiceCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetServiceCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
