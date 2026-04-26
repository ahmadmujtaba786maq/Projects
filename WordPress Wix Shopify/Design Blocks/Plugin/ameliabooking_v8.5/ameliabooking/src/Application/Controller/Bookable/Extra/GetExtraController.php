<?php

namespace AmeliaBooking\Application\Controller\Bookable\Extra;

use AmeliaBooking\Application\Commands\Bookable\Extra\GetExtraCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetExtraController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Extra
 */
class GetExtraController extends Controller
{
    /**
     * Instantiates the Get Extra command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetExtraCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetExtraCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
