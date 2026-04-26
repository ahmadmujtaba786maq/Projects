<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Payment;

use AmeliaBooking\Application\Controller\Payment\AddPaymentController;
use AmeliaBooking\Application\Controller\Payment\DeletePaymentController;
use AmeliaBooking\Application\Controller\Payment\CalculatePaymentAmountController;
use AmeliaBooking\Application\Controller\Payment\GetPaymentController;
use AmeliaBooking\Application\Controller\Payment\GetPaymentsController;
use AmeliaBooking\Application\Controller\Payment\UpdatePaymentController;
use Slim\App;

/**
 * Class Payment
 *
 * @package AmeliaBooking\Infrastructure\Routes\Payment
 */
class Payment
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/payments', GetPaymentsController::class);

        $app->get('/payments/{id:[0-9]+}', GetPaymentController::class);

        $app->post('/payments', AddPaymentController::class);

        $app->post('/payments/delete/{id:[0-9]+}', DeletePaymentController::class);

        $app->post('/payments/{id:[0-9]+}', UpdatePaymentController::class);

        $app->post('/payments/amount', CalculatePaymentAmountController::class);
    }
}
