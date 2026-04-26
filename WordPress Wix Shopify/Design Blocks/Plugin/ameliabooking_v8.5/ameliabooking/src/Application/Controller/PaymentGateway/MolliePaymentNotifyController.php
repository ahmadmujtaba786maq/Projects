<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\MolliePaymentNotifyCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class MolliePaymentNotifyController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class MolliePaymentNotifyController extends Controller
{
    /**
     * Fields for Mollie payment that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'id',
        'name',
    ];

    /**
     * Instantiates the Mollie Payment Notify command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return MolliePaymentNotifyCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new MolliePaymentNotifyCommand($args);

        $this->setCommandFields($command, $request->getParsedBody());

        $this->setCommandFields($command, $request->getQueryParams());

        return $command;
    }
}
