<?php

namespace AmeliaBooking\Application\Controller\User\Provider;

use AmeliaBooking\Application\Commands\User\Provider\UpdateProviderStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateProviderStatusController
 *
 * @package AmeliaBooking\Application\Controller\User\Provider
 */
class UpdateProviderStatusController extends Controller
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
     * @return UpdateProviderStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateProviderStatusCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
