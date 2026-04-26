<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Bookable;

use AmeliaBooking\Application\Controller\Bookable\Extra\AddExtraController;
use AmeliaBooking\Application\Controller\Bookable\Extra\DeleteExtraController;
use AmeliaBooking\Application\Controller\Bookable\Extra\GetExtraController;
use AmeliaBooking\Application\Controller\Bookable\Extra\GetExtrasController;
use AmeliaBooking\Application\Controller\Bookable\Extra\UpdateExtraController;
use Slim\App;

/**
 * Class Extra
 *
 * @package AmeliaBooking\Infrastructure\Routes\Bookable
 */
class Extra
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/extras', GetExtrasController::class);

        $app->get('/extras/{id:[0-9]+}', GetExtraController::class);

        $app->post('/extras', AddExtraController::class);

        $app->post('/extras/delete/{id:[0-9]+}', DeleteExtraController::class);

        $app->post('/extras/{id:[0-9]+}', UpdateExtraController::class);
    }
}
