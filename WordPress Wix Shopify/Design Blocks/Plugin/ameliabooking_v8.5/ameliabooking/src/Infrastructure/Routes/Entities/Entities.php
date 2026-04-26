<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Entities;

use AmeliaBooking\Application\Controller\Entities\GetEntitiesController;
use Slim\App;

/**
 * Class Entities
 *
 * @package AmeliaBooking\Infrastructure\Routes\Entities
 */
class Entities
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/entities', GetEntitiesController::class);
    }
}
