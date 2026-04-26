<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\GetPackagesCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetPackagesController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class GetPackagesController extends Controller
{
    /**
     * Instantiates the Get Packages command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetPackagesCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetPackagesCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
