<?php

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\PaymentCallbackCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class PaymentCallbackController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class PaymentCallbackController extends Controller
{
    /**
     * Fields for PayPal payment that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'fromLink',
        'paymentAmeliaId',
        'paymentMethod',
        'razorpay_payment_link_id',
        'razorpay_payment_id',
        'razorpay_payment_link_reference_id',
        'razorpay_payment_link_status',
        'razorpay_signature',
        'id',
        'chargedAmount',
        'payPalStatus',
        'token',
        'PayerID',
        'paymentId',
        'fromPanel',
        'session_id',
        'method',
        'accountId',
        'orderId',
        'squareOrderId'
    ];

    /**
     * Instantiates the Payment Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return PaymentCallbackCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new PaymentCallbackCommand($args);

        $this->setCommandFields($command, $request->getParsedBody());

        $this->setCommandFields($command, $request->getQueryParams());

        return $command;
    }
}
