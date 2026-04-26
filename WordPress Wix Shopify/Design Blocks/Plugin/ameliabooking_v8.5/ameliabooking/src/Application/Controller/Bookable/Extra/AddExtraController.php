<?php

namespace AmeliaBooking\Application\Controller\Bookable\Extra;

use AmeliaBooking\Application\Commands\Bookable\Extra\AddExtraCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddExtraController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Extra
 */
class AddExtraController extends Controller
{
    /**
     * Fields for extra that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'name',
        'duration',
        'price',
        'description',
        'maxQuantity',
        'position',
        'aggregatedPrice',
        'translations',
        'serviceId'
    ];

    /**
     * Instantiates the Add Extra command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddExtraCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddExtraCommand($args);

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
        $eventBus->emit('bookable.extra.added', $result);
    }
}
