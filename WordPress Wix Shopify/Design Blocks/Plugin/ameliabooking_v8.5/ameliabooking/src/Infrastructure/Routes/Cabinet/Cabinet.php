<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Cabinet;

use AmeliaBooking\Application\Controller\User\Customer\ReauthorizeController;
use AmeliaBooking\Application\Controller\User\LoginCabinetController;
use AmeliaBooking\Application\Controller\User\LogoutCabinetController;
use Slim\App;

/**
 * Class Cabinet
 *
 * @package AmeliaBooking\Infrastructure\Routes\Cabinet
 */
class Cabinet
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->post('/users/authenticate', LoginCabinetController::class);

        $app->post('/users/logout', LogoutCabinetController::class);

        $app->post('/users/customers/reauthorize', ReauthorizeController::class);
    }
}
