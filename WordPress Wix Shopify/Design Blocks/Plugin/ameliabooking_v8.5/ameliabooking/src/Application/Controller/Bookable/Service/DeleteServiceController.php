<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\DeleteServiceCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class DeleteServiceController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class DeleteServiceController extends Controller
{
    /**
     * Instantiates the Delete Service command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteServiceCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteServiceCommand($args);
        $requestBody = $request->getParsedBody();
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
        $eventBus->emit('bookable.service.deleted', $result);
    }
}
