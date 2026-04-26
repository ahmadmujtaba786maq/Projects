<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes;

use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Licence;
use Slim\App;

/**
 * Class Routes
 *
 * API Routes for the Amelia app
 *
 * @package AmeliaBooking\Infrastructure\Routes
 */
class Routes
{
    /**
     * @param App       $app
     * @param Container $container
     */
    public static function routes(App $app, Container $container)
    {
        Licence\Licence::setRoutes($app, $container);
    }
}
