<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Search;

use AmeliaBooking\Application\Commands\Search\GetSearchCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetSearchController
 *
 * @package AmeliaBooking\Application\Controller\Search
 */
class GetSearchController extends Controller
{
    /**
     * Instantiates the Get Search command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetSearchCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetSearchCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $params['services'] = !empty($params['services']) ? array_map('intval', $params['services']) : 0;

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
