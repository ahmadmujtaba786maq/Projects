<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Test;

use AmeliaBooking\Application\Controller\Test\TestController;
use Slim\App;

/**
 * Class Test
 *
 * @package AmeliaBooking\Infrastructure\Routes\Test
 */
class Test
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/test', TestController::class);
    }
}
