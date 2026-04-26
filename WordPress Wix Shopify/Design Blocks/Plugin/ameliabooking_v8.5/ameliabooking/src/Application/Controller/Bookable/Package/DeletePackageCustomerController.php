<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\DeletePackageCustomerCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class DeletePackageCustomerController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class DeletePackageCustomerController extends Controller
{
    /**
     * Instantiates the Delete Package Customer command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeletePackageCustomerCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DeletePackageCustomerCommand($args);

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
        $eventBus->emit('PackageCustomerDeleted', $result);
    }
}
