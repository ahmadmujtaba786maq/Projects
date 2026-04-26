<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\WhatsAppWebhookRegisterCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class WhatsAppWebhookController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class WhatsAppWebhookRegisterController extends Controller
{
    /**
     * Fields for notification that can be received from front-end
     *
     * @var bool
     */
    protected $sendJustData = true;

    /**
     * Instantiates the Whatsapp Webhook command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return WhatsAppWebhookRegisterCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new WhatsAppWebhookRegisterCommand($args);
        $params  = (array)$request->getQueryParams();

        $command->setField('params', $params);

        $requestBody = $request->getQueryParams();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
