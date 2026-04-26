<?php

namespace AmeliaBooking\Application\Controller\User\Provider;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\User\Provider\UpdateProviderCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateProviderController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class UpdateProviderController extends Controller
{
    /**
     * Fields for provider that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'type',
        'firstName',
        'lastName',
        'birthday',
        'email',
        'employeeAppleCalendar',
        'externalId',
        'locationId',
        'avatar',
        'phone',
        'countryPhoneIso',
        'note',
        'description',
        'gender',
        'serviceList',
        'weekDayList',
        'specialDayList',
        'timeOutList',
        'periodList',
        'dayOffList',
        'pictureFullPath',
        'pictureThumbPath',
        'zoomUserId',
        'appleCalendarId',
        'googleCalendar',
        'outlookCalendar',
        'password',
        'sendEmployeePanelAccessEmail',
        'translations',
        'timeZone',
        'badgeId',
        'stripeConnect',
        'show'
    ];

    /**
     * Instantiates the Update Provider command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateProviderCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateProviderCommand($args);

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
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
        $eventBus->emit('provider.updated', $result);
    }
}
