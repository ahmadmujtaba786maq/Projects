<?php

namespace AmeliaBooking\Application\Controller\Mailchimp;

use AmeliaBooking\Application\Commands\Mailchimp\GetMailchimpAuthURLCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetMailchimpAuthURLController
 *
 * @package AmeliaBooking\Application\Controller\Mailchimp
 */
class GetMailchimpAuthURLController extends Controller
{
    /**
     * Instantiates the Get Mailchimp Auth URL command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetMailchimpAuthURLCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetMailchimpAuthURLCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
