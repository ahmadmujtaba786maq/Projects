<?php

namespace AmeliaBooking\Application\Controller\Square;

use AmeliaBooking\Application\Commands\Square\SquarePaymentCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class SquarePaymentController
 *
 * @package AmeliaBooking\Application\Controller\Square
 */
class SquarePaymentController extends Controller
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
        'isCart',
        'recurring',
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
     * Instantiates the Square Payment Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SquarePaymentCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new SquarePaymentCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
