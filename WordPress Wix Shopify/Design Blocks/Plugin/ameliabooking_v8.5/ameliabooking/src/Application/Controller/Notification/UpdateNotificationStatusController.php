<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\UpdateNotificationStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateNotificationStatusController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class UpdateNotificationStatusController extends Controller
{
    /**
     * Fields for notification that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status'
    ];

    /**
     * Instantiates the Update Notification Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateNotificationStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateNotificationStatusCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
