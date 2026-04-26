<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\AddPaymentCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddPaymentController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class AddPaymentController extends Controller
{
    /**
     * @var array
     */
    protected $allowedFields = [
        'customerBookingId',
        'dateTime',
        'status',
        'gateway',
        'gatewayTitle',
        'data',
        'packageCustomerId',
        'amount',
        'entity',
        'actionsCompleted',
        'wcOrderId',
        'transactionId'
    ];

    /**
     * Instantiates the Add Payment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddPaymentCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new AddPaymentCommand($args);
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
        $eventBus->emit('payment.added', $result);
    }
}
