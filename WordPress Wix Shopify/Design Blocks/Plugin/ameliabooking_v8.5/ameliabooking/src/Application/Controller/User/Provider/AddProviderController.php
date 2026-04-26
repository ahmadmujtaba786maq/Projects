<?php

namespace AmeliaBooking\Application\Controller\User\Provider;

use AmeliaBooking\Application\Commands\User\Provider\AddProviderCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddProviderController
 *
 * @package AmeliaBooking\Application\Controller\User\Provider
 */
class AddProviderController extends Controller
{
    /**
     * Fields for provider that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'type',
        'status',
        'firstName',
        'lastName',
        'birthday',
        'email',
        'externalId',
        'locationId',
        'avatar',
        'phone',
        'countryPhoneIso',
        'note',
        'description',
        'serviceList',
        'weekDayList',
        'specialDayList',
        'timeOutList',
        'periodList',
        'dayOffList',
        'externalId',
        'pictureFullPath',
        'pictureThumbPath',
        'password',
        'sendEmployeePanelAccessEmail',
        'translations',
        'timeZone',
        'badgeId',
        'stripeConnect',
        'show',
    ];

    /**
     * Instantiates the Add Provider command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddProviderCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddProviderCommand($args);

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
        $eventBus->emit('provider.added', $result);
    }
}
