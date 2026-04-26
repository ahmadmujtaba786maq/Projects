<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\SendAmeliaSmsApiRequestCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class SendAmeliaSmsApiRequestController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class SendAmeliaSmsApiRequestController extends Controller
{
    /**
     * Fields for SMS API that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'process',
        'data',
        'type'
    ];

    /**
     * Instantiates the Send Amelia SMS API Request command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return SendAmeliaSmsApiRequestCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new SendAmeliaSmsApiRequestCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
