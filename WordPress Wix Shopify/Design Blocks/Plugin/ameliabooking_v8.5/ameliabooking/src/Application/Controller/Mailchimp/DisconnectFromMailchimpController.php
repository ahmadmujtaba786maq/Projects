<?php

namespace AmeliaBooking\Application\Controller\Mailchimp;

use AmeliaBooking\Application\Commands\Mailchimp\DisconnectFromMailchimpCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class DisconnectFromMailchimpController
 *
 * @package AmeliaBooking\Application\Controller\Mailchimp
 */
class DisconnectFromMailchimpController extends Controller
{
    /**
     * Instantiates the DisconnectFromMailchimpCommand to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DisconnectFromMailchimpCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DisconnectFromMailchimpCommand($args);

        $this->setCommandFields($command, $request->getQueryParams());

        return $command;
    }
}
