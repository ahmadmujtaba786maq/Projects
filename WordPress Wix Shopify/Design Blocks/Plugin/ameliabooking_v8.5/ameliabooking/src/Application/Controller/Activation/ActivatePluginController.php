<?php

namespace AmeliaBooking\Application\Controller\Activation;

use AmeliaBooking\Application\Commands\Activation\ActivatePluginCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class ActivatePluginController
 *
 * @package AmeliaBooking\Application\Controller\Activation
 */
class ActivatePluginController extends Controller
{
    /**
     * Instantiates the Activate Plugin command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return ActivatePluginCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new ActivatePluginCommand($args);
        $command->setField('params', (array)$request->getQueryParams());
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
