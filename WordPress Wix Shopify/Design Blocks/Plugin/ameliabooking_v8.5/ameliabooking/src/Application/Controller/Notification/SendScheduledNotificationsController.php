<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\SendScheduledNotificationsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class SendScheduledNotificationsController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class SendScheduledNotificationsController extends Controller
{
    /**
     * Instantiates the Send Scheduled Notifications command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SendScheduledNotificationsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new SendScheduledNotificationsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
