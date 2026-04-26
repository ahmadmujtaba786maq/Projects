<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\WhatsNew;

use AmeliaBooking\Application\Controller\WhatsNew\GetWhatsNewController;
use Slim\App;

/**
 * Class WhatsNew
 *
 * @package AmeliaBooking\Infrastructure\Routes\WhatsNew
 */
class WhatsNew
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/whats-new', GetWhatsNewController::class);
    }
}
