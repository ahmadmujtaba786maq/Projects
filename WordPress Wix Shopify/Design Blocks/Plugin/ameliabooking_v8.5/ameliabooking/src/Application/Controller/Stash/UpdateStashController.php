<?php

namespace AmeliaBooking\Application\Controller\Stash;

use AmeliaBooking\Application\Commands\Stash\UpdateStashCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateStashController
 *
 * @package AmeliaBooking\Application\Controller\Stash
 */
class UpdateStashController extends Controller
{
    /**
     * Instantiates the Update Stash command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateStashCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateStashCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

        return $command;
    }
}
