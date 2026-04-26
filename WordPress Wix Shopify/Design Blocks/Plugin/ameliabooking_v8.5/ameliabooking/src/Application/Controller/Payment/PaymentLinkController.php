<?php

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\PaymentCallbackCommand;
use AmeliaBooking\Application\Commands\Payment\PaymentLinkCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class PaymentLinkController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class PaymentLinkController extends Controller
{
    protected $allowedFields = [
        'data',
        'paymentMethod',
        'redirectUrl'
    ];

    /**
     * Instantiates the Payment Link command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return PaymentLinkCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new PaymentLinkCommand($args);
        $command->setField('data', (array)$request->getParsedBody());
        return $command;
    }
}
