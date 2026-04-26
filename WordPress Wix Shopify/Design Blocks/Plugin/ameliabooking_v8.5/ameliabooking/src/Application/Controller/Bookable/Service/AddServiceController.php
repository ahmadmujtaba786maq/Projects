<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\AddServiceCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddServiceController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class AddServiceController extends Controller
{
    /**
     * Fields for service that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'categoryId',
        'color',
        'description',
        'duration',
        'extras',
        'gallery',
        'maxCapacity',
        'maxExtraPeople',
        'minCapacity',
        'name',
        'pictureFullPath',
        'pictureThumbPath',
        'price',
        'providers',
        'status',
        'timeAfter',
        'bringingAnyone',
        'show',
        'aggregatedPrice',
        'settings',
        'recurringCycle',
        'recurringSub',
        'recurringPayment',
        'timeBefore',
        'translations',
        'deposit',
        'depositPayment',
        'depositPerPerson',
        'fullPayment',
        'mandatoryExtra',
        'minSelectedExtras',
        'customPricing',
        'limitPerCustomer',
    ];

    /**
     * Instantiates the Add Service command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddServiceCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddServiceCommand($args);

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
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
        $eventBus->emit('bookable.service.added', $result);
    }
}
