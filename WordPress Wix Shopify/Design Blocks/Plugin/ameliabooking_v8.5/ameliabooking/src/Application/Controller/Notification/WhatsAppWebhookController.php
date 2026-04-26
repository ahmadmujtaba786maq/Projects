<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Notification\WhatsAppWebhookCommand;
use AmeliaBooking\Application\Commands\Notification\WhatsAppWebhookRegisterCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class WhatsAppWebhookController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class WhatsAppWebhookController extends Controller
{
    protected $allowedFields = [
        'entry'
    ];

    /**
     * Instantiates the Whatsapp Webhook command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return WhatsAppWebhookCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new WhatsAppWebhookCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
