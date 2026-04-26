<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Square;

use AmeliaBooking\Application\Controller\Square\DisconnectFromSquareAccountController;
use AmeliaBooking\Application\Controller\Square\FetchAccessTokenSquareController;
use AmeliaBooking\Application\Controller\Square\GetSquareAuthURLController;
use AmeliaBooking\Application\Controller\Square\SquarePaymentController;
use AmeliaBooking\Application\Controller\Square\SquarePaymentNotifyController;
use AmeliaBooking\Application\Controller\Square\SquareRefundWebhookController;
use Slim\App;

/**
 * Class Square
 *
 * @package AmeliaBooking\Infrastructure\Routes\Square
 */
class Square
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/square/authorization/url', GetSquareAuthURLController::class);

        $app->get('/square/authorization/token', FetchAccessTokenSquareController::class);

        $app->post('/square/disconnect', DisconnectFromSquareAccountController::class);

        $app->post('/square/refund', SquareRefundWebhookController::class);


        $app->post('/payment/square', SquarePaymentController::class);

        $app->get('/payment/square/notify', SquarePaymentNotifyController::class);
    }
}
