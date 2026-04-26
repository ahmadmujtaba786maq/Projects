<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\SendUndeliveredNotificationsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class SendUndeliveredNotificationsController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class SendUndeliveredNotificationsController extends Controller
{
    /**
     * Instantiates the Send Undelivered Notifications command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SendUndeliveredNotificationsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new SendUndeliveredNotificationsCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
