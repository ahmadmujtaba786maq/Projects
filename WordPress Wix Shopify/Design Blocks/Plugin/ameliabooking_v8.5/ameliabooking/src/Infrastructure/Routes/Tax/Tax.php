<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Tax;

use AmeliaBooking\Application\Controller\Tax\AddTaxController;
use AmeliaBooking\Application\Controller\Tax\DeleteTaxController;
use AmeliaBooking\Application\Controller\Tax\GetTaxController;
use AmeliaBooking\Application\Controller\Tax\GetTaxesController;
use AmeliaBooking\Application\Controller\Tax\UpdateTaxController;
use AmeliaBooking\Application\Controller\Tax\UpdateTaxStatusController;
use Slim\App;

/**
 * Class Tax
 *
 * @package AmeliaBooking\Infrastructure\Routes\Tax
 */
class Tax
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/taxes', GetTaxesController::class);

        $app->get('/taxes/{id:[0-9]+}', GetTaxController::class);

        $app->post('/taxes', AddTaxController::class);

        $app->post('/taxes/delete/{id:[0-9]+}', DeleteTaxController::class);

        $app->post('/taxes/{id:[0-9]+}', UpdateTaxController::class);

        $app->post('/taxes/status/{id:[0-9]+}', UpdateTaxStatusController::class);
    }
}
