<?php

namespace AmeliaBooking\Application\Controller\Bookable\Extra;

use AmeliaBooking\Application\Commands\Bookable\Extra\UpdateExtraCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateExtraController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Extra
 */
class UpdateExtraController extends Controller
{
    /**
     * Fields for extra that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'name',
        'description',
        'price',
        'maxQuantity',
        'duration',
        'position',
        'serviceId',
        'aggregatedPrice',
        'translations',
    ];

    /**
     * Instantiates the Update Extra command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateExtraCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateExtraCommand($args);

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
        $eventBus->emit('bookable.extra.updated', $result);
    }
}
