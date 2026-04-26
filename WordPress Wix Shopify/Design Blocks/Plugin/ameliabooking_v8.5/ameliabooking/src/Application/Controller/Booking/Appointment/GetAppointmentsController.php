<?php

namespace AmeliaBooking\Application\Controller\Booking\Appointment;

use AmeliaBooking\Application\Commands\Booking\Appointment\GetAppointmentsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetAppointmentsController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Appointment
 */
class GetAppointmentsController extends Controller
{
    /**
     * Instantiates the Get Appointments command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetAppointmentsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetAppointmentsCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
            unset($params['source']);
        }

        $this->setArrayParams($params);

        if (!empty($params['providers'])) {
            $params['providers'] = array_map('intval', $params['providers']);
        }

        if (!empty($params['services'])) {
            $params['services'] = array_map('intval', $params['services']);
        }

        if (!empty($params['packages'])) {
            $params['packages'] = array_map('intval', $params['packages']);
        }

        $command->setField('params', $params);

        $command->setToken($request);

        return $command;
    }
}
