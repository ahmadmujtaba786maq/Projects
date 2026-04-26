<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\UpdatePackageCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdatePackageController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class UpdatePackageController extends Controller
{
    /**
     * Fields for package that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'color',
        'description',
        'gallery',
        'name',
        'pictureFullPath',
        'pictureThumbPath',
        'price',
        'position',
        'calculatedPrice',
        'discount',
        'bookable',
        'status',
        'settings',
        'endDate',
        'durationCount',
        'durationType',
        'translations',
        'deposit',
        'depositPayment',
        'fullPayment',
        'sharedCapacity',
        'quantity',
        'limitPerCustomer',
    ];

    /**
     * Instantiates the Update Package command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdatePackageCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdatePackageCommand($args);

        $command->setField('id', (int)$command->getArg('id'));

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
