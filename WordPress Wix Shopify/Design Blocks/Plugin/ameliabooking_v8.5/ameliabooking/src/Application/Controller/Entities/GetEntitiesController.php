<?php

namespace AmeliaBooking\Application\Controller\Entities;

use AmeliaBooking\Application\Commands\Entities\GetEntitiesCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class GetEntitiesController
 *
 * @package AmeliaBooking\Application\Controller\Entities
 */
class GetEntitiesController extends Controller
{
    /**
     * Instantiates the Get Entities command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetEntitiesCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetEntitiesCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
            unset($params['source']);
        }

        $command->setToken($request);

        $this->setArrayParams($params);

        $command->setField('params', $params);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
