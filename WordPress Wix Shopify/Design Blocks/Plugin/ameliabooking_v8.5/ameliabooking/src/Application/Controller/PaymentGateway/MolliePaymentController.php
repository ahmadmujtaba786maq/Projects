<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\MolliePaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class MolliePaymentController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class MolliePaymentController extends Controller
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
     * @return MolliePaymentCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new MolliePaymentCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
