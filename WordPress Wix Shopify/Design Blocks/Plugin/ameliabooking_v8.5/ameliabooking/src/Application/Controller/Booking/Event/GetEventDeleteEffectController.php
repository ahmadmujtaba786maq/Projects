<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\GetEventDeleteEffectCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetEventDeleteEffectController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class GetEventDeleteEffectController extends Controller
{
    /**
     * Instantiates the Get Event Delete Effect command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetEventDeleteEffectCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetEventDeleteEffectCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
