<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\AddBookingCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class AddBookingController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class AddBookingController extends Controller
{
    /**
     * Fields for booking that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'type',
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'eventId',
        'serviceId',
        'providerId',
        'locationId',
        'couponCode',
        'validateCoupon',
        'payment',
        'recurring',
        'isCart',
        'packageId',
        'package',
        'packageRules',
        'utcOffset',
        'locale',
        'timeZone',
        'urlParams',
        'utc',
        'recaptcha',
        'deposit',
        'ticketsData',
        'isBackendOrCabinet',
        'packageBookingFromBackend',
        'runInstantPostBookingActions'
    ];

    /**
     * Instantiates the Add Booking command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddBookingCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddBookingCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
            unset($params['source']);
        }

        $command->setToken($request);

        return $command;
    }
}
