<?php

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\GetServiceDeleteEffectCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetServiceDeleteEffectController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class GetServiceDeleteEffectController extends Controller
{
    /**
     * Instantiates the Get Service Delete Effect command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetServiceDeleteEffectCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetServiceDeleteEffectCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
