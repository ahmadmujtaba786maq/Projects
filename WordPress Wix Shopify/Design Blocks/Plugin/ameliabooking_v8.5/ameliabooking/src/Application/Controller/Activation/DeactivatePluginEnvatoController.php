<?php

namespace AmeliaBooking\Application\Controller\Activation;

use AmeliaBooking\Application\Commands\Activation\DeactivatePluginEnvatoCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DeactivatePluginEnvatoController
 *
 * @package AmeliaBooking\Application\Controller\Activation
 */
class DeactivatePluginEnvatoController extends Controller
{
    /**
     * Instantiates the Deactivate Plugin Envato command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeactivatePluginEnvatoCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DeactivatePluginEnvatoCommand($args);
        $command->setField('params', (array)$request->getQueryParams());
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
