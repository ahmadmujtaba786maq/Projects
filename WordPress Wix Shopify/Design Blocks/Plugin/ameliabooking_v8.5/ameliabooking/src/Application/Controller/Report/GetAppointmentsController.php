<?php

namespace AmeliaBooking\Application\Controller\Report;

use AmeliaBooking\Application\Commands\Report\GetAppointmentsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetAppointmentsController
 *
 * @package AmeliaBooking\Application\Controller\Report
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

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
