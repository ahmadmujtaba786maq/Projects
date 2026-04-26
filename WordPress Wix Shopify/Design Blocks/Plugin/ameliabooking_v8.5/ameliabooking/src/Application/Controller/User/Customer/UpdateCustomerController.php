<?php

namespace AmeliaBooking\Application\Controller\User\Customer;

use AmeliaBooking\Application\Commands\User\Customer\UpdateCustomerCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateCustomerController
 *
 * @package AmeliaBooking\Application\Controller\User\Customer
 */
class UpdateCustomerController extends Controller
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
        'note',
        'gender',
        'password',
        'countryPhoneIso',
        'pictureFullPath',
        'pictureThumbPath',
        'translations',
        'customFields'
    ];

    /**
     * Instantiates the Update Customer command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateCustomerCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateCustomerCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);
        $command->setField('id', $args['id']);
        $command->setToken($request);

        $params = $request->getQueryParams();

        if (isset($params['source'])) {
            $command->setPage($params['source']);
        }

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
        $eventBus->emit('user.updated', $result);
    }
}
