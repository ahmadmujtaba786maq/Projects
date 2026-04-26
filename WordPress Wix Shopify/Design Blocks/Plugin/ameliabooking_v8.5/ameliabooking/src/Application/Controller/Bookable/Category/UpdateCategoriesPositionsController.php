<?php

namespace AmeliaBooking\Application\Controller\Bookable\Category;

use AmeliaBooking\Application\Commands\Bookable\Category\UpdateCategoriesPositionsCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class UpdateCategoriesPositionsController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Category
 */
class UpdateCategoriesPositionsController extends Controller
{
    /**
     * Fields for category that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'categories'
    ];

    /**
     * @param Request $request
     * @param         $args
     *
     * @return UpdateCategoriesPositionsCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateCategoriesPositionsCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
