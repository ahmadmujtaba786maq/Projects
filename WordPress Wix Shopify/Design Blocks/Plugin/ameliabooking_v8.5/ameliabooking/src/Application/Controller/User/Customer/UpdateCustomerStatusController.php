<?php

namespace AmeliaBooking\Application\Controller\User\Customer;

use AmeliaBooking\Application\Commands\User\Customer\UpdateCustomerStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateCustomerStatusController
 *
 * @package AmeliaBooking\Application\Controller\User\Customer
 */
class UpdateCustomerStatusController extends Controller
{
    /**
     * Fields for provider that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Provider Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateCustomerStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateCustomerStatusCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
