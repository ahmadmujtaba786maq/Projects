<?php

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\UpdatePackagesPositionsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdatePackagesPositionsController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class UpdatePackagesPositionsController extends Controller
{
    /**
     * Fields for package that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'packages',
        'sorting'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return UpdatePackagesPositionsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdatePackagesPositionsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
