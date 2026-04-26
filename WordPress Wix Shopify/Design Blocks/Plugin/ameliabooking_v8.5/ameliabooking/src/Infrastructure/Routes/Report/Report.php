<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Report;

use AmeliaBooking\Application\Controller\Report\GetCustomersController;
use AmeliaBooking\Application\Controller\Report\GetAppointmentsController;
use AmeliaBooking\Application\Controller\Report\GetPaymentsController;
use AmeliaBooking\Application\Controller\Report\GetCouponsController;
use AmeliaBooking\Application\Controller\Report\GetEventAttendeesController;
use Slim\App;

/**
 * Class Report
 *
 * @package AmeliaBooking\Infrastructure\Routes\Report
 */
class Report
{
    /**
     * @param App $app
     *
     * @throws \InvalidArgumentException
     */
    public static function routes(App $app)
    {
        $app->post('/report/customers', GetCustomersController::class)->setOutputBuffering(false);

        $app->post('/report/appointments', GetAppointmentsController::class)->setOutputBuffering(false);

        $app->post('/report/payments', GetPaymentsController::class)->setOutputBuffering(false);

        $app->post('/report/coupons', GetCouponsController::class)->setOutputBuffering(false);

        $app->post('/report/event/attendees', GetEventAttendeesController::class)->setOutputBuffering(false);
    }
}
