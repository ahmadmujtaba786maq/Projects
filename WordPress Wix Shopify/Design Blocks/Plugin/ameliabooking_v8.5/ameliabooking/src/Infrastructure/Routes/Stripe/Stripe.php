<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Stripe;

use AmeliaBooking\Application\Controller\Stripe\GetStripeAccountController;
use AmeliaBooking\Application\Controller\Stripe\GetStripeAccountsController;
use AmeliaBooking\Application\Controller\Stripe\GetStripeAccountDashboardUrlController;
use AmeliaBooking\Application\Controller\Stripe\StripeAccountDisconnectController;
use AmeliaBooking\Application\Controller\Stripe\StripeOnboardRedirectController;
use Slim\App;

/**
 * Class Stripe
 *
 * @package AmeliaBooking\Infrastructure\Routes\Stripe
 */
class Stripe
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/stripe/accounts', GetStripeAccountsController::class);

        $app->get('/stripe/accounts/{id:[0-9]+}', GetStripeAccountController::class);

        $app->post('/stripe/onboard/{id:[0-9]+}', StripeOnboardRedirectController::class);

        $app->post('/stripe/dashboard/{id:[0-9]+}', GetStripeAccountDashboardUrlController::class);

        $app->post('/stripe/disconnect/{id:[0-9]+}', StripeAccountDisconnectController::class);
    }
}
