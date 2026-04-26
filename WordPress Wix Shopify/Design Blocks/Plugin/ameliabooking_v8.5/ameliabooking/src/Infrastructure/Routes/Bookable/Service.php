<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Bookable;

use AmeliaBooking\Application\Controller\Bookable\Service\AddServiceController;
use AmeliaBooking\Application\Controller\Bookable\Service\DeleteServiceController;
use AmeliaBooking\Application\Controller\Bookable\Service\GetServiceController;
use AmeliaBooking\Application\Controller\Bookable\Service\GetServiceDeleteEffectController;
use AmeliaBooking\Application\Controller\Bookable\Service\GetServicesController;
use AmeliaBooking\Application\Controller\Bookable\Service\UpdateServiceController;
use AmeliaBooking\Application\Controller\Bookable\Service\UpdateServicesPositionsController;
use AmeliaBooking\Application\Controller\Bookable\Service\UpdateServiceStatusController;
use Slim\App;

/**
 * Class Service
 *
 * @package AmeliaBooking\Infrastructure\Routes\Bookable
 */
class Service
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/services', GetServicesController::class);

        $app->get('/services/{id:[0-9]+}', GetServiceController::class);

        $app->post('/services', AddServiceController::class);

        $app->post('/services/delete/{id:[0-9]+}', DeleteServiceController::class);

        $app->post('/services/{id:[0-9]+}', UpdateServiceController::class);

        $app->get('/services/effect/{id:[0-9]+}', GetServiceDeleteEffectController::class);

        $app->post('/services/status/{id:[0-9]+}', UpdateServiceStatusController::class);

        $app->post('/services/positions', UpdateServicesPositionsController::class);
    }
}
