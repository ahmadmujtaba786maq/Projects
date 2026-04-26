<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Resource;

use AmeliaBooking\Application\Commands\Bookable\Resource\UpdateResourceStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class UpdateResourceStatusController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Resource
 */
class UpdateResourceStatusController extends Controller
{
    /**
     * Fields for resource that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Package Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateResourceStatusCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateResourceStatusCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
