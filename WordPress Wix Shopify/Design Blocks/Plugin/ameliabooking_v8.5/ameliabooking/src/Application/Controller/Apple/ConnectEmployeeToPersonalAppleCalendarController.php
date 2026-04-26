<?php

namespace AmeliaBooking\Application\Controller\Apple;

use AmeliaBooking\Application\Commands\Apple\ConnectEmployeeToPersonalAppleCalendarCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

class ConnectEmployeeToPersonalAppleCalendarController extends Controller
{
    protected $allowedFields = [
        'employeeAppleCalendar'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return ConnectEmployeeToPersonalAppleCalendarCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new ConnectEmployeeToPersonalAppleCalendarCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        return $command;
    }
}
