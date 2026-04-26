<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\CustomField;

use AmeliaBooking\Application\Commands\CustomField\GetCustomFieldFileCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetCustomFieldFileController
 *
 * @package AmeliaBooking\Application\Controller\CustomField
 */
class GetCustomFieldFileController extends Controller
{
    /**
     * Instantiates the Get CustomField File command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetCustomFieldFileCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
            unset($params['source']);
        }

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);
        if (isset($params['token'])) {
            $command->token = $params['token'];
        }
        return $command;
    }
}
