<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\AddPackageCustomerCommand;
use AmeliaBooking\Application\Commands\Bookable\Package\UpdatePackageCustomerCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class AddPackageCustomerController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class AddPackageCustomerController extends Controller
{
    /**
     * Fields for package that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'packageId',
        'customerId',
        'couponId',
        'rules',
        'notify',
        'couponId',
    ];

    /**
     * Instantiates the Update Package Customer command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddPackageCustomerCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddPackageCustomerCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        $command->setToken($request);

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
        $eventBus->emit('PackageCustomerAdded', $result);
    }
}
