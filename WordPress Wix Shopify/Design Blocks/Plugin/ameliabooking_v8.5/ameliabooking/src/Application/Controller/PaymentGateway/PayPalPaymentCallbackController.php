<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\PayPalPaymentCallbackCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class PayPalPaymentCallbackController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class PayPalPaymentCallbackController extends Controller
{
    /**
     * Fields for PayPal payment that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
        'token',
        'PayerID',
    ];

    /**
     * Instantiates the PayPal Payment Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return PayPalPaymentCallbackCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new PayPalPaymentCallbackCommand($args);
        $command->setField('token', (string)$request->getQueryParam('token', ''));
        $command->setField('PayerID', (string)$request->getQueryParam('PayerID', ''));
        $command->setField('status', (string)$request->getQueryParam('status', ''));
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
