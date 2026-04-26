<?php

namespace AmeliaBooking\Application\Controller\User\Provider;

use AmeliaBooking\Application\Commands\User\Provider\GetProvidersCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class GetProvidersController
 *
 * @package AmeliaBooking\Application\Controller\User\Provider
 */
class GetProvidersController extends Controller
{
    /**
     * @param Request $request
     * @param         $args
     *
     * @return GetProvidersCommand
     * @throws \Exception
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetProvidersCommand($args);

        $params = (array)$request->getQueryParams();

        if (isset($params['services'])) {
            $params['services'] = array_map('intval', $params['services']);
        }

        $command->setField('params', $params);

        $requestBody = $request->getQueryParams();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('providers.returned', $result);
    }
}
