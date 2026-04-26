<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Google;

use AmeliaBooking\Application\Controller\Google\DisconnectFromGoogleAccountController;
use AmeliaBooking\Application\Controller\Google\FetchAccessTokenWithAuthCodeController;
use AmeliaBooking\Application\Controller\Google\GetGoogleAuthURLController;
use Slim\App;

/**
 * Class Google
 *
 * @package AmeliaBooking\Infrastructure\Routes\Google
 */
class Google
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/google/authorization/url/{id:[0-9]+}', GetGoogleAuthURLController::class);

        $app->post('/google/authorization/url/{id:[0-9]+}', GetGoogleAuthURLController::class);

        $app->post('/google/disconnect/{id:[0-9]+}', DisconnectFromGoogleAccountController::class);

        $app->post('/google/authorization/token', FetchAccessTokenWithAuthCodeController::class);
    }
}
