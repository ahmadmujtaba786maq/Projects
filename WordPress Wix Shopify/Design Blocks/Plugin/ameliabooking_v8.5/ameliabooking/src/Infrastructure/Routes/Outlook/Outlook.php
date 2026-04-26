<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Outlook;

use AmeliaBooking\Application\Controller\Outlook\DisconnectFromOutlookAccountController;
use AmeliaBooking\Application\Controller\Outlook\FetchAccessTokenWithAuthCodeOutlookController;
use AmeliaBooking\Application\Controller\Outlook\GetOutlookAuthURLController;
use Slim\App;

/**
 * Class Outlook
 *
 * @package AmeliaBooking\Infrastructure\Routes\Outlook
 */
class Outlook
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/outlook/authorization/url/{id:[0-9]+}', GetOutlookAuthURLController::class);

        $app->post('/outlook/disconnect/{id:[0-9]+}', DisconnectFromOutlookAccountController::class);

        $app->post('/outlook/authorization/token', FetchAccessTokenWithAuthCodeOutlookController::class);
    }
}
