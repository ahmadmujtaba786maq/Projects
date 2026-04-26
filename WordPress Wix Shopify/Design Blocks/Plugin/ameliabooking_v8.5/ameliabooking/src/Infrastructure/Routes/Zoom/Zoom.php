<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Zoom;

use AmeliaBooking\Application\Controller\Zoom\GetUsersController;
use Slim\App;

/**
 * Class Zoom
 *
 * @package AmeliaBooking\Infrastructure\Routes\Zoom
 */
class Zoom
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/zoom/users', GetUsersController::class);
    }
}
