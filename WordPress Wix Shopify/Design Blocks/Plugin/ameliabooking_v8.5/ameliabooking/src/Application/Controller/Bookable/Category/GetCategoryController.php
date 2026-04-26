<?php

namespace AmeliaBooking\Application\Controller\Bookable\Category;

use AmeliaBooking\Application\Commands\Bookable\Category\GetCategoryCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetCategoryController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Category
 */
class GetCategoryController extends Controller
{
    /**
     * Instantiates the Get Category command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetCategoryCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetCategoryCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
