<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Settings;

use AmeliaBooking\Application\Controller\Settings\GetSettingsController;
use AmeliaBooking\Application\Controller\Settings\UpdateSettingsController;
use Slim\App;

/**
 * Class Settings
 *
 * @package AmeliaBooking\Infrastructure\Routes\Settings
 */
class Settings
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/settings', GetSettingsController::class);

        $app->post('/settings', UpdateSettingsController::class);
    }
}
