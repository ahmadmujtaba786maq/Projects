<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\MolliePaymentCommand;
use AmeliaBooking\Application\Commands\PaymentGateway\RazorpayPaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class RazorpayPaymentController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class RazorpayPaymentController extends Controller
{
    /**
     * Fields for Mollie payment that can be received from API
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
        'componentProps',
        'returnUrl',
    ];

    /**
     * Instantiates the Mollie Payment Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return RazorpayPaymentCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new RazorpayPaymentCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
