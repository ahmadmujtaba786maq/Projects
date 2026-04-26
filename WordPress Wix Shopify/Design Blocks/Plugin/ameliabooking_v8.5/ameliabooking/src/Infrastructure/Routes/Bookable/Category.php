<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Bookable;

use AmeliaBooking\Application\Controller\Bookable\Category\AddCategoryController;
use AmeliaBooking\Application\Controller\Bookable\Category\DeleteCategoryController;
use AmeliaBooking\Application\Controller\Bookable\Category\GetCategoriesController;
use AmeliaBooking\Application\Controller\Bookable\Category\GetCategoryController;
use AmeliaBooking\Application\Controller\Bookable\Category\UpdateCategoriesPositionsController;
use AmeliaBooking\Application\Controller\Bookable\Category\UpdateCategoryController;
use Slim\App;

/**
 * Class Category
 *
 * @package AmeliaBooking\Infrastructure\Routes\Bookable
 */
class Category
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/categories', GetCategoriesController::class);

        $app->get('/categories/{id:[0-9]+}', GetCategoryController::class);

        $app->post('/categories', AddCategoryController::class);

        $app->post('/categories/delete/{id:[0-9]+}', DeleteCategoryController::class);

        $app->post('/categories/{id:[0-9]+}', UpdateCategoryController::class);

        $app->post('/categories/positions', UpdateCategoriesPositionsController::class);
    }
}
