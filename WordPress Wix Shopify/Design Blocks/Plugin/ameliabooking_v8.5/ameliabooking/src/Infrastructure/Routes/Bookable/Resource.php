<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Bookable;

use AmeliaBooking\Application\Controller\Bookable\Resource\AddResourceController;
use AmeliaBooking\Application\Controller\Bookable\Resource\DeleteResourceController;
use AmeliaBooking\Application\Controller\Bookable\Resource\GetResourcesController;
use AmeliaBooking\Application\Controller\Bookable\Resource\UpdateResourceController;
use AmeliaBooking\Application\Controller\Bookable\Resource\UpdateResourceStatusController;
use Slim\App;

/**
 * Class Resource
 *
 * @package AmeliaBooking\Infrastructure\Routes\Bookable
 */
class Resource
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/resources', GetResourcesController::class);

        $app->post('/resources', AddResourceController::class);

        $app->post('/resources/delete/{id:[0-9]+}', DeleteResourceController::class);

        $app->post('/resources/{id:[0-9]+}', UpdateResourceController::class);

        $app->post('/resources/status/{id:[0-9]+}', UpdateResourceStatusController::class);
    }
}
