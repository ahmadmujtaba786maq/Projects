<?php

namespace AmeliaBooking\Application\Controller\Apple;

use AmeliaBooking\Application\Commands\Apple\DisconnectEmployeeFromAppleCalendarCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

class DisconnectEmployeeFromAppleCalendarController extends Controller
{
    /**
     * @param Request $request
     * @param         $args
     *
     * @return DisconnectEmployeeFromAppleCalendarCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DisconnectEmployeeFromAppleCalendarCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        return $command;
    }
}
