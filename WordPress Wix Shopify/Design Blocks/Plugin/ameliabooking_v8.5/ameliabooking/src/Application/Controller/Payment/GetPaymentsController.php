<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\GetPaymentsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetPaymentsController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class GetPaymentsController extends Controller
{
    /**
     * Instantiates the Get Payments command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetPaymentsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetPaymentsCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        if (isset($params['services'])) {
            $params['services'] = array_map('intval', $params['services']);
        }

        $command->setField('params', $params);

        $requestBody = $request->getQueryParams();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
