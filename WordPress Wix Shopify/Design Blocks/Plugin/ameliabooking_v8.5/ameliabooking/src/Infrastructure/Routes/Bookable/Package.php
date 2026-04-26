<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Bookable;

use AmeliaBooking\Application\Controller\Bookable\Package\AddPackageController;
use AmeliaBooking\Application\Controller\Bookable\Package\AddPackageCustomerController;
use AmeliaBooking\Application\Controller\Bookable\Package\DeletePackageController;
use AmeliaBooking\Application\Controller\Bookable\Package\DeletePackageCustomerController;
use AmeliaBooking\Application\Controller\Bookable\Package\GetPackageDeleteEffectController;
use AmeliaBooking\Application\Controller\Bookable\Package\GetPackagesController;
use AmeliaBooking\Application\Controller\Bookable\Package\GetPackageController;
use AmeliaBooking\Application\Controller\Bookable\Package\UpdatePackageController;
use AmeliaBooking\Application\Controller\Bookable\Package\UpdatePackageCustomerController;
use AmeliaBooking\Application\Controller\Bookable\Package\UpdatePackagesPositionsController;
use AmeliaBooking\Application\Controller\Bookable\Package\UpdatePackageStatusController;
use Slim\App;

/**
 * Class Package
 *
 * @package AmeliaBooking\Infrastructure\Routes\Bookable
 */
class Package
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/packages', GetPackagesController::class);

        $app->post('/packages', AddPackageController::class);

        $app->post('/packages/delete/{id:[0-9]+}', DeletePackageController::class);

        $app->post('/packages/{id:[0-9]+}', UpdatePackageController::class);

        $app->get('/packages/effect/{id:[0-9]+}', GetPackageDeleteEffectController::class);

        $app->post('/packages/status/{id:[0-9]+}', UpdatePackageStatusController::class);

        $app->post('/packages/positions', UpdatePackagesPositionsController::class);

        $app->post('/packages/customers', AddPackageCustomerController::class);

        $app->post('/packages/customers/{id:[0-9]+}', UpdatePackageCustomerController::class);

        $app->post('/packages/customers/delete/{id:[0-9]+}', DeletePackageCustomerController::class);
    }
}
