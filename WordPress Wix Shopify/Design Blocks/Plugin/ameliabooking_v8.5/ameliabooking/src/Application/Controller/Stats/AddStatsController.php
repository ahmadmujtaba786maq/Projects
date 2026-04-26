<?php

namespace AmeliaBooking\Application\Controller\Stats;

use AmeliaBooking\Application\Commands\Stats\AddStatsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class AddStatsController
 *
 * @package AmeliaBooking\Application\Controller\Stats
 */
class AddStatsController extends Controller
{
    /**
     * Fields for stats that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'locationId',
        'providerId',
        'serviceId'
    ];

    /**
     * Instantiates the Add Stats command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddStatsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new AddStatsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
