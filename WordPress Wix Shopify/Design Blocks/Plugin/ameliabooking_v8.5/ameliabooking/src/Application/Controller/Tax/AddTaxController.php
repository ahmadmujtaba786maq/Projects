<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Tax;

use AmeliaBooking\Application\Commands\Tax\AddTaxCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class AddTaxController
 *
 * @package AmeliaBooking\Application\Controller\Tax
 */
class AddTaxController extends Controller
{
    /**
     * Fields for tax that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
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
     * Instantiates the Add Tax command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddTaxCommand($args);

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
        $eventBus->emit('tax.added', $result);
    }
}
