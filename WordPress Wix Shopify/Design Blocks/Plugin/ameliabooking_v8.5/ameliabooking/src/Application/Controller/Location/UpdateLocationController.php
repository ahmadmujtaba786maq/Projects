<?php

namespace AmeliaBooking\Application\Controller\Location;

use AmeliaBooking\Application\Commands\Location\UpdateLocationCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateLocationController
 *
 * @package AmeliaBooking\Application\Controller\Location
 */
class UpdateLocationController extends Controller
{
    /**
     * Fields for location that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
        'name',
        'description',
        'address',
        'phone',
        'latitude',
        'longitude',
        'pictureFullPath',
        'pictureThumbPath',
        'pin',
        'translations'
    ];

    /**
     * Instantiates the Update Location command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateLocationCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateLocationCommand($args);

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
        $eventBus->emit('location.updated', $result);
    }
}
