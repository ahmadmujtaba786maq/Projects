<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Payment\RefundPaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class RefundPaymentController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class RefundPaymentController extends Controller
{
    /**
     * Instantiates the Refund Payment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return RefundPaymentCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new RefundPaymentCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('PaymentRefunded', $result);
    }
}
