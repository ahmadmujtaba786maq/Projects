<?php

namespace AmeliaBooking\Application\Controller\Bookable\Category;

use AmeliaBooking\Application\Commands\Bookable\Category\GetCategoriesCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetCategoriesController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Category
 */
class GetCategoriesController extends Controller
{
    /**
     * Instantiates the Get Categories command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetCategoriesCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GetCategoriesCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
