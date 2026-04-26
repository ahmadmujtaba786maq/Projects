<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\PayPalPaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class PayPalPaymentController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class PayPalPaymentController extends Controller
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
     * Instantiates the PayPal Payment Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return PayPalPaymentCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new PayPalPaymentCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
