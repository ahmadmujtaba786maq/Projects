<?php

namespace AmeliaBooking\Application\Controller\Report;

use AmeliaBooking\Application\Commands\Report\GetPaymentsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetPaymentsController
 *
 * @package AmeliaBooking\Application\Controller\Report
 */
class GetPaymentsController extends Controller
{
    /**
     * Instantiates the Get Report Customers command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetPaymentsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetPaymentsCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
