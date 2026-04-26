<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Payment;

use AmeliaBooking\Application\Controller\Payment\GetTransactionAmountController;
use AmeliaBooking\Application\Controller\Payment\RefundPaymentController;
use Slim\App;

/**
 * Class Refund
 *
 * @package AmeliaBooking\Infrastructure\Routes\Payment
 */
class Refund
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->post('/payments/refund/{id:[0-9]+}', RefundPaymentController::class);

        $app->get('/payments/transaction/{id:[0-9]+}', GetTransactionAmountController::class);
    }
}
