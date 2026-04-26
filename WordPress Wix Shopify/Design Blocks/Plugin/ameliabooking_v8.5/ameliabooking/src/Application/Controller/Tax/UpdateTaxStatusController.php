<?php

namespace AmeliaBooking\Application\Controller\Tax;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Tax\UpdateTaxStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateTaxStatusController
 *
 * @package AmeliaBooking\Application\Controller\Tax
 */
class UpdateTaxStatusController extends Controller
{
    /**
     * Fields for Tax that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Tax Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateTaxStatusCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateTaxStatusCommand($args);

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
        $eventBus->emit('tax.updated', $result);
    }
}
