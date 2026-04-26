<?php

namespace AmeliaBooking\Application\Controller\Bookable\Extra;

use AmeliaBooking\Application\Commands\Bookable\Extra\GetExtrasCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetExtrasController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Extra
 */
class GetExtrasController extends Controller
{
    /**
     * Instantiates the Get Extras command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetExtrasCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetExtrasCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
