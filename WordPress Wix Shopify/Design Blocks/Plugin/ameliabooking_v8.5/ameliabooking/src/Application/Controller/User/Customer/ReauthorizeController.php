<?php

namespace AmeliaBooking\Application\Controller\User\Customer;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\User\Customer\ReauthorizeCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class ReauthorizeController
 *
 * @package AmeliaBooking\Application\Controller\User\Customer
 */
class ReauthorizeController extends Controller
{
    /**
     * Fields for user that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'email',
        'locale',
        'cabinetType',
        'recaptcha',
    ];

    /**
     * Instantiates the Forgot Password command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return ReauthorizeCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new ReauthorizeCommand($args);

        $requestBody = $request->getParsedBody();
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
    }
}
