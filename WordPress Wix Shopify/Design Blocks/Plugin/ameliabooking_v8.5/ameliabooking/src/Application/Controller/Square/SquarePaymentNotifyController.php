<?php

namespace AmeliaBooking\Application\Controller\Square;

use AmeliaBooking\Application\Commands\Square\SquarePaymentNotifyCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class SquarePaymentNotifyController
 *
 * @package AmeliaBooking\Application\Controller\Square
 */
class SquarePaymentNotifyController extends Controller
{
    /**
     * Fields for Mollie payment that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'name',
        'returnUrl',
        'orderId',
        'squareOrderId',
        'fragment'
    ];

    /**
     * Instantiates the Square Payment Notify command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SquarePaymentNotifyCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new SquarePaymentNotifyCommand($args);

        $this->setCommandFields($command, $request->getParsedBody());

        $this->setCommandFields($command, $request->getQueryParams());

        return $command;
    }
}
