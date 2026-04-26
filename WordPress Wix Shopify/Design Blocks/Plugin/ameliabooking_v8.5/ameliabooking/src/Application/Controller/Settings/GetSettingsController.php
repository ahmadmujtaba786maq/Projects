<?php

namespace AmeliaBooking\Application\Controller\Settings;

use AmeliaBooking\Application\Commands\Settings\GetSettingsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetSettingsController
 *
 * @package AmeliaBooking\Application\Controller\Settings
 */
class GetSettingsController extends Controller
{
    /**
     * @param Request $request
     * @param         $args
     *
     * @return GetSettingsCommand
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetSettingsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
