<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\GetCalendarEventsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetCalendarEventsController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class GetCalendarEventsController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'providers',
        'eventIds',
        'periods',
        'recurring'
    ];

    /**
     * Instantiates the Add Event command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetCalendarEventsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetCalendarEventsCommand($args);

        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);
        $command->setToken($request);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
        }

        return $command;
    }
}
