<?php

namespace AmeliaBooking\Application\Controller\Report;

use AmeliaBooking\Application\Commands\Report\GetEventAttendeesCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetEventAttendeesController
 *
 * @package AmeliaBooking\Application\Controller\Report
 */
class GetEventAttendeesController extends Controller
{
    /**
     * Instantiates the Get Event Attendees command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetEventAttendeesCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetEventAttendeesCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
