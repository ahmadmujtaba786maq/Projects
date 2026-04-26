<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Stash;

use AmeliaBooking\Application\Controller\Stash\UpdateStashController;
use Slim\App;

/**
 * Class Stash
 *
 * @package AmeliaBooking\Infrastructure\Routes\Stash
 */
class Stash
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->post('/stash', UpdateStashController::class);
    }
}
