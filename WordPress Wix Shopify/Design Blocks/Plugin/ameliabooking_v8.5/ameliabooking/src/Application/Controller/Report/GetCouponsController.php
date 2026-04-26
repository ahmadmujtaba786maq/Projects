<?php

namespace AmeliaBooking\Application\Controller\Report;

use AmeliaBooking\Application\Commands\Report\GetCouponsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetCouponsController
 *
 * @package AmeliaBooking\Application\Controller\Report
 */
class GetCouponsController extends Controller
{
    /**
     * Instantiates the Get Report Coupons command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetCouponsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetCouponsCommand($args);

        $params = (array)$request->getQueryParams();

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
