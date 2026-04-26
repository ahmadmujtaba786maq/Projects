<?php

namespace AmeliaBooking\Application\Controller\WhatsNew;

use AmeliaBooking\Application\Commands\WhatsNew\GetWhatsNewCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateStashController
 *
 * @package AmeliaBooking\Application\Controller\Stash
 */
class GetWhatsNewController extends Controller
{
    /**
     * Instantiates the Update Stash command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetWhatsNewCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetWhatsNewCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
