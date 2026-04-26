<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Stats;

use AmeliaBooking\Application\Commands\Stats\GetStatsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetStatsController
 *
 * @package AmeliaBooking\Application\Controller\Stats
 */
class GetStatsController extends Controller
{
    /**
     * Instantiates the Get Stats command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetStatsCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getQueryParams();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
