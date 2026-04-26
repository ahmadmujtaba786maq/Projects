<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\GetEventBookingsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetEventBookingsController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class GetEventBookingsController extends Controller
{
    /**
     * Instantiates the Get Event Bookings command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetEventBookingsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetEventBookingsCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
            unset($params['source']);
        }

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $command->setToken($request);

        return $command;
    }
}
