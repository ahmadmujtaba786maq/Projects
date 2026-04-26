<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Import;

use AmeliaBooking\Application\Controller\Import\ImportCustomersController;
use Slim\App;

/**
 *
 * Class Import
 *
 * @package AmeliaBooking\Infrastructure\Routes\Import
 */
class Import
{
    /**
     * @param App $app
     *
     * @throws \InvalidArgumentException
     */
    public static function routes(App $app)
    {
        $app->post('/import/customers', ImportCustomersController::class)->setOutputBuffering(false);
    }
}
