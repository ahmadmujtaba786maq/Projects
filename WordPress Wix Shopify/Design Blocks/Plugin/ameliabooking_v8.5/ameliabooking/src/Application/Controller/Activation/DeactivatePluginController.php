<?php

namespace AmeliaBooking\Application\Controller\Activation;

use AmeliaBooking\Application\Commands\Activation\DeactivatePluginCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DeactivatePluginController
 *
 * @package AmeliaBooking\Application\Controller\Activation
 */
class DeactivatePluginController extends Controller
{
    /**
     * Instantiates the Deactivate Plugin command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeactivatePluginCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DeactivatePluginCommand($args);
        $command->setField('params', (array)$request->getQueryParams());
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
