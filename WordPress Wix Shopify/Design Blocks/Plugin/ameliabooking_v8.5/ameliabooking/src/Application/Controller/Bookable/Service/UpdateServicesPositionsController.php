<?php

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\UpdateServicesPositionsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateServicesPositionsController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class UpdateServicesPositionsController extends Controller
{
    /**
     * Fields for service that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'services',
        'sorting'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return UpdateServicesPositionsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateServicesPositionsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
