<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Resource;

use AmeliaBooking\Application\Commands\Bookable\Resource\DeleteResourceCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class DeleteResourceController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Resource
 */
class DeleteResourceController extends Controller
{
    /**
     * Instantiates the Delete Resource command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeleteResourceCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new DeleteResourceCommand($args);

        $requestBody = $request->getParsedBody();

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
