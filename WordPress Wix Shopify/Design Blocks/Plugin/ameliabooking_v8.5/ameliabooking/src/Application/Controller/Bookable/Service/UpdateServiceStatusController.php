<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Service;

use AmeliaBooking\Application\Commands\Bookable\Service\UpdateServiceStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateServiceStatusController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Service
 */
class UpdateServiceStatusController extends Controller
{
    /**
     * Fields for service that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Service Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateServiceStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateServiceStatusCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
