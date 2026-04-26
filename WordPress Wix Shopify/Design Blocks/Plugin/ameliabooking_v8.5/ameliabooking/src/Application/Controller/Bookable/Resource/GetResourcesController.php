<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Resource;

use AmeliaBooking\Application\Commands\Bookable\Resource\GetResourcesCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetResourcesController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Resource
 */
class GetResourcesController extends Controller
{
    /**
     * Instantiates the Get Resources command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetResourcesCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetResourcesCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        $params = (array)$request->getQueryParams();

        $command->setField('params', $params);

        return $command;
    }
}
