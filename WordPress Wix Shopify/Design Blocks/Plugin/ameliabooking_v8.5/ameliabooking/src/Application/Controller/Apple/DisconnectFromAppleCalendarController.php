<?php

namespace AmeliaBooking\Application\Controller\Apple;

use AmeliaBooking\Application\Commands\Apple\DisconnectFromAppleCalendarCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DisconnectFromAppleCalendarController
 *
 * @package AmeliaBooking\Application\Controller\Apple
 */
class DisconnectFromAppleCalendarController extends Controller
{
    /**
     * @param Request $request
     * @param         $args
     *
     * @return DisconnectFromAppleCalendarCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DisconnectFromAppleCalendarCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        return $command;
    }
}
