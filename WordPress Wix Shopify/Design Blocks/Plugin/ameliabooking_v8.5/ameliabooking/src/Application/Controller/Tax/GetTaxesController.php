<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Tax;

use AmeliaBooking\Application\Commands\Tax\GetTaxesCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetTaxesController
 *
 * @package AmeliaBooking\Application\Controller\Tax
 */
class GetTaxesController extends Controller
{
    /**
     * Instantiates the Get Tax command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetTaxesCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['services'])) {
            $params['services'] = array_map('intval', $params['services']);
        }

        if (isset($params['events'])) {
            $params['events'] = array_map('intval', $params['events']);
        }

        if (isset($params['packages'])) {
            $params['packages'] = array_map('intval', $params['packages']);
        }

        $command->setField('params', $params);

        $requestBody = $request->getQueryParams();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
