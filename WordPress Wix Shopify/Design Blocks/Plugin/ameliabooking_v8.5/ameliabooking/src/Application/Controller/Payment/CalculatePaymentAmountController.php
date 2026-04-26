<?php

namespace AmeliaBooking\Application\Controller\Payment;

use AmeliaBooking\Application\Commands\Payment\CalculatePaymentAmountCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class CalculatePaymentAmountController
 *
 * @package AmeliaBooking\Application\Controller\Payment
 */
class CalculatePaymentAmountController extends Controller
{
    /**
     * Fields for PayPal payment that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'type',
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'eventId',
        'serviceId',
        'providerId',
        'locationId',
        'couponCode',
        'payment',
        'recurring',
        'isCart',
        'recaptcha',
        'packageId',
        'package',
        'packageRules',
        'utcOffset',
        'locale',
        'timeZone',
        'deposit',
    ];

    /**
     * Instantiates the CalculatePaymentAmountCommand Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return CalculatePaymentAmountCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new CalculatePaymentAmountCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
