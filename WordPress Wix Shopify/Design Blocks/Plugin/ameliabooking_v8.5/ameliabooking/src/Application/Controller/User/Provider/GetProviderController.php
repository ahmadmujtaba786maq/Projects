<?php

namespace AmeliaBooking\Application\Controller\User\Provider;

use AmeliaBooking\Application\Commands\User\Provider\GetProviderCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class GetProviderController
 *
 * @package AmeliaBooking\Application\Controller\User\Provider
 */
class GetProviderController extends Controller
{
    /**
     * Instantiates the Get Provider command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetProviderCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $getUserCommand = new GetProviderCommand($args);
        $requestBody    = $request->getParsedBody();
        $this->setCommandFields($getUserCommand, $requestBody);

        return $getUserCommand;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('provider.returned', $result);
    }
}
