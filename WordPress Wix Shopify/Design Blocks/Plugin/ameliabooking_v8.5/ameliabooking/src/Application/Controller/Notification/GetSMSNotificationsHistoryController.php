<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\GetSMSNotificationsHistoryCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetSMSNotificationsHistoryController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class GetSMSNotificationsHistoryController extends Controller
{
    /**
     * Instantiates the Get SMS Notifications History command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetSMSNotificationsHistoryCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetSMSNotificationsHistoryCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getQueryParams();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
