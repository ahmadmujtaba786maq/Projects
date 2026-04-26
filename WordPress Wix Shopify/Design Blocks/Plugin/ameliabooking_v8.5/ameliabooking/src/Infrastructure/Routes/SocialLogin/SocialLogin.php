<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\SocialLogin;

use AmeliaBooking\Application\Controller\User\Authentication\SocialLoginController;
use Slim\App;

/**
 * Class SocialLogin
 *
 * @package AmeliaBooking\Infrastructure\Routes\SocialLogin
 */
class SocialLogin
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        // Authentication
        $app->post('/users/authentication/{provider}', SocialLoginController::class);
    }
}
