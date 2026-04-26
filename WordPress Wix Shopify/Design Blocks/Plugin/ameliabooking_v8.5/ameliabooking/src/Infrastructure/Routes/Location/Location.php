<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Location;

use AmeliaBooking\Application\Controller\Location\AddLocationController;
use AmeliaBooking\Application\Controller\Location\DeleteLocationController;
use AmeliaBooking\Application\Controller\Location\GetLocationController;
use AmeliaBooking\Application\Controller\Location\GetLocationsController;
use AmeliaBooking\Application\Controller\Location\UpdateLocationController;
use AmeliaBooking\Application\Controller\Location\UpdateLocationStatusController;
use AmeliaBooking\Application\Controller\Location\GetLocationDeleteEffectController;
use Slim\App;

/**
 * Class Location
 *
 * @package AmeliaBooking\Infrastructure\Routes\Location
 */
class Location
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/locations/{id:[0-9]+}', GetLocationController::class);

        $app->get('/locations', GetLocationsController::class);

        $app->post('/locations', AddLocationController::class);

        $app->post('/locations/delete/{id:[0-9]+}', DeleteLocationController::class);

        $app->post('/locations/{id:[0-9]+}', UpdateLocationController::class);

        $app->post('/locations/status/{id:[0-9]+}', UpdateLocationStatusController::class);

        $app->get('/locations/effect/{id:[0-9]+}', GetLocationDeleteEffectController::class);
    }
}
