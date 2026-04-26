<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\UpdateSMSNotificationHistoryCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateSMSNotificationHistoryController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class UpdateSMSNotificationHistoryController extends Controller
{
    /**
     * @var array
     */
    protected $allowedFields = [
        'status',
        'price',
        'dateTime',
        'logId'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return UpdateSMSNotificationHistoryCommand|mixed
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateSMSNotificationHistoryCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
