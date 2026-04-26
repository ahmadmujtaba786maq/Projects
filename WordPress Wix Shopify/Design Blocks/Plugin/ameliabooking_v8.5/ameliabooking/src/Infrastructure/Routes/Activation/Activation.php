<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Activation;

use AmeliaBooking\Application\Controller\Activation\ActivatePluginController;
use AmeliaBooking\Application\Controller\Activation\DeactivatePluginController;
use AmeliaBooking\Application\Controller\Activation\DeactivatePluginEnvatoController;
use AmeliaBooking\Application\Controller\Activation\ParseDomainController;
use Slim\App;

/**
 * Class Activation
 *
 * @package AmeliaBooking\Infrastructure\Routes\Activation
 */
class Activation
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/activation/code', ActivatePluginController::class);

        $app->get('/activation/code/deactivate', DeactivatePluginController::class);

        $app->get('/activation/envato/deactivate', DeactivatePluginEnvatoController::class);

        $app->post('/activation/parse-domain', ParseDomainController::class);
    }
}
