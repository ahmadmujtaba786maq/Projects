<?php

namespace AmeliaBooking\Application\Controller\Bookable\Extra;

use AmeliaBooking\Application\Commands\Bookable\Extra\DeleteExtraCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class DeleteExtraController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Extra
 */
class DeleteExtraController extends Controller
{
    /**
     * Instantiates the Delete Extra command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteExtraCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeleteExtraCommand($args);
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
        $eventBus->emit('bookable.extra.deleted', $result);
    }
}
