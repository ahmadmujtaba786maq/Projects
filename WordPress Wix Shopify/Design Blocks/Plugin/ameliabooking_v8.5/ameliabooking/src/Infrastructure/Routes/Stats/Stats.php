<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Stats;

use AmeliaBooking\Application\Controller\Stats\AddStatsController;
use AmeliaBooking\Application\Controller\Stats\GetStatsController;
use Slim\App;

/**
 * Class Stats
 *
 * @package AmeliaBooking\Infrastructure\Routes\Stats
 */
class Stats
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/stats', GetStatsController::class);

        $app->post('/stats', AddStatsController::class);
    }
}
