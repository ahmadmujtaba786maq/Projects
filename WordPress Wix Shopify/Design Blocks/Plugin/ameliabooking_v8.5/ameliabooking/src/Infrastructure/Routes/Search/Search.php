<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Search;

use AmeliaBooking\Application\Controller\Search\GetSearchController;
use Slim\App;

/**
 * Class Search
 *
 * @package AmeliaBooking\Infrastructure\Routes\Search
 */
class Search
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/search', GetSearchController::class);
    }
}
