<?php

namespace AmeliaBooking\Application\Controller\User\Customer;

use AmeliaBooking\Application\Commands\User\Customer\AddCustomerCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddCustomerController
 *
 * @package AmeliaBooking\Application\Controller\User\Customer
 */
class AddCustomerController extends Controller
{
    /**
     * Fields for user that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
        'type',
        'firstName',
        'lastName',
        'birthday',
        'email',
        'externalId',
        'avatar',
        'phone',
        'countryPhoneIso',
        'note',
        'gender',
        'externalId',
        'pictureFullPath',
        'pictureThumbPath',
        'translations',
        'customFields'
    ];

    /**
     * Instantiates the Add Customer command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddCustomerCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new AddCustomerCommand($args);
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
        $eventBus->emit('user.added', $result);
    }
}
