<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Payment\GetTransactionAmountCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class GetTransactionAmountController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class GetTransactionAmountController extends Controller
{
    /**
     * Instantiates the Refund Payment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetTransactionAmountCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetTransactionAmountCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
