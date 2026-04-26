<?php

namespace AmeliaBooking\Application\Controller\User;

use AmeliaBooking\Application\Commands\User\GetWPUsersCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetWPUsersController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class GetWPUsersController extends Controller
{
    /**
     * Instantiates the Get WP Users command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetWPUsersCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetWPUsersCommand($args);
        $command->setField('id', (int)$request->getQueryParam('id'));
        $command->setField('role', $request->getQueryParam('role'));
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
