<?php

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\GetPackageDeleteEffectCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetPackageDeleteEffectController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class GetPackageDeleteEffectController extends Controller
{
    /**
     * Instantiates the Get Package Delete Effect command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetPackageDeleteEffectCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetPackageDeleteEffectCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
