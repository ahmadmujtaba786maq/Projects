<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\DeletePackageCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class DeletePackageController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class DeletePackageController extends Controller
{
    /**
     * Instantiates the Delete Package command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return DeletePackageCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new DeletePackageCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
