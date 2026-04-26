<?php

namespace AmeliaBooking\Application\Controller\Report;

use AmeliaBooking\Application\Commands\Report\GetCustomersCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetCustomersController
 *
 * @package AmeliaBooking\Application\Controller\Report
 */
class GetCustomersController extends Controller
{
    /**
     * Instantiates the Get Report Customers command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetCustomersCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetCustomersCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
