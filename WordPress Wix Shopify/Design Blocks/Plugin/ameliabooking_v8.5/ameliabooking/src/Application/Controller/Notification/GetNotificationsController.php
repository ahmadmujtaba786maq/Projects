<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\GetNotificationsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetNotificationsController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class GetNotificationsController extends Controller
{
    /**
     * Instantiates the Get Notification command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetNotificationsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetNotificationsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
