<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\SendTestEmailCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class SendTestEmailController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class SendTestEmailController extends Controller
{
    /**
     * Fields for notification that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'notificationTemplate',
        'recipientEmail',
        'type',
        'language'
    ];

    /**
     * Instantiates the Send Test Email command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SendTestEmailCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new SendTestEmailCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
