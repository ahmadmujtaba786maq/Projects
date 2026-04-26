<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Tax;

use AmeliaBooking\Application\Commands\Tax\UpdateTaxCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateTaxController
 *
 * @package AmeliaBooking\Application\Controller\Tax
 */
class UpdateTaxController extends Controller
{
    /**
     * Fields for Tax that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'id',
        'name',
        'type',
        'amount',
        'status',
        'allServices',
        'allExtras',
        'allEvents',
        'allPackages',
        'services',
        'extras',
        'events',
        'packages',
    ];


    /**
     * Instantiates the Update Tax command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateTaxCommand($args);

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
