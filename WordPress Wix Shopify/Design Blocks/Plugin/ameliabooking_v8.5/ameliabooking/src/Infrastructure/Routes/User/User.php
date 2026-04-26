<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\User;

use AmeliaBooking\Application\Controller\User\Customer\GetCustomersController;
use AmeliaBooking\Application\Controller\User\Customer\GetCustomerController;
use AmeliaBooking\Application\Controller\User\Customer\AddCustomerController;
use AmeliaBooking\Application\Controller\User\Customer\UpdateCustomerController;
use AmeliaBooking\Application\Controller\User\Customer\UpdateCustomerStatusController;
use AmeliaBooking\Application\Controller\User\DeleteUserController;
use AmeliaBooking\Application\Controller\User\GetCurrentUserController;
use AmeliaBooking\Application\Controller\User\GetUserDeleteEffectController;
use AmeliaBooking\Application\Controller\User\GetWPUsersController;
use AmeliaBooking\Application\Controller\User\Provider\UpdateProviderStatusController;
use AmeliaBooking\Application\Controller\User\Provider\GetProviderController;
use AmeliaBooking\Application\Controller\User\Provider\GetProvidersController;
use AmeliaBooking\Application\Controller\User\Provider\AddProviderController;
use AmeliaBooking\Application\Controller\User\Provider\UpdateProviderController;
use Slim\App;

/**
 * Class User
 *
 * @package AmeliaBooking\Infrastructure\Routes\User
 */
class User
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/users/wp-users', GetWPUsersController::class);

        // Customers
        $app->get('/users/customers/{id:[0-9]+}', GetCustomerController::class);

        $app->get('/users/customers', GetCustomersController::class);

        $app->post('/users/customers', AddCustomerController::class);

        $app->post('/users/customers/{id:[0-9]+}', UpdateCustomerController::class);

        $app->post('/users/customers/delete/{id:[0-9]+}', DeleteUserController::class);

        $app->get('/users/customers/effect/{id:[0-9]+}', GetUserDeleteEffectController::class);

        $app->post('/users/customers/status/{id:[0-9]+}', UpdateCustomerStatusController::class);

        // Providers
        $app->get('/users/providers/{id:[0-9]+}', GetProviderController::class);

        $app->get('/users/providers', GetProvidersController::class);

        $app->post('/users/providers', AddProviderController::class);

        $app->post('/users/providers/{id:[0-9]+}', UpdateProviderController::class);

        $app->post('/users/providers/status/{id:[0-9]+}', UpdateProviderStatusController::class);

        $app->post('/users/providers/delete/{id:[0-9]+}', DeleteUserController::class);

        $app->get('/users/providers/effect/{id:[0-9]+}', GetUserDeleteEffectController::class);

        // Current User
        $app->get('/users/current', GetCurrentUserController::class);
    }
}
