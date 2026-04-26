<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\GetPaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetPaymentController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class GetPaymentController extends Controller
{
    /**
     * Instantiates the Get Payment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetPaymentCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetPaymentCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
