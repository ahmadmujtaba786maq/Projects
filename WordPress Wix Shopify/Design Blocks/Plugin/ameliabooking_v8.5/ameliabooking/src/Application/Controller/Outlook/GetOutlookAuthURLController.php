<?php

namespace AmeliaBooking\Application\Controller\Outlook;

use AmeliaBooking\Application\Commands\Outlook\GetOutlookAuthURLCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetOutlookAuthURLController
 *
 * @package AmeliaBooking\Application\Controller\Outlook
 */
class GetOutlookAuthURLController extends Controller
{
    /**
     * Instantiates the Get Outlook Auth URL command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetOutlookAuthURLCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetOutlookAuthURLCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
