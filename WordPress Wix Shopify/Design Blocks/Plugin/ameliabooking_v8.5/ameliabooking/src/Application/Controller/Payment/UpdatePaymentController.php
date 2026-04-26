<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\UpdatePaymentCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdatePaymentController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class UpdatePaymentController extends Controller
{
    /**
     * @var array
     */
    protected $allowedFields = [
        'customerBookingId',
        'packageCustomerId',
        'dateTime',
        'status',
        'gateway',
        'gatewayTitle',
        'data',
        'amount',
        'transactionId',
        'wcOrderId',
    ];

    /**
     * Instantiates the Update Payment command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdatePaymentCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $addPaymentCommand = new UpdatePaymentCommand($args);
        $requestBody       = $request->getParsedBody();
        $this->setCommandFields($addPaymentCommand, $requestBody);

        return $addPaymentCommand;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('payment.updated', $result);
    }
}
