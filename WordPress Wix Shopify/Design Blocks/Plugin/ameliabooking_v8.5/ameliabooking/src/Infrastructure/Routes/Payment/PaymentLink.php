<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Payment;

use AmeliaBooking\Application\Controller\Payment\PaymentCallbackController;
use AmeliaBooking\Application\Controller\Payment\PaymentLinkController;
use Slim\App;

/**
 * Class PaymentLink
 *
 * @package AmeliaBooking\Infrastructure\Routes\Payment
 */
class PaymentLink
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->post('/payments/link', PaymentLinkController::class);

        $app->get('/payments/callback', PaymentCallbackController::class);

        $app->post('/payments/callback', PaymentCallbackController::class);
    }
}
