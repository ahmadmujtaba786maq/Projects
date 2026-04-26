<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\GetEventsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetEventsController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class GetEventsController extends Controller
{
    /**
     * Instantiates the Get Events command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetEventsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetEventsCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
            unset($params['source']);
        }

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $command->setToken($request);

        return $command;
    }
}
