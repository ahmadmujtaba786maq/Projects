<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Coupon;

use AmeliaBooking\Application\Controller\Coupon\AddCouponController;
use AmeliaBooking\Application\Controller\Coupon\DeleteCouponController;
use AmeliaBooking\Application\Controller\Coupon\GetCouponController;
use AmeliaBooking\Application\Controller\Coupon\GetCouponsController;
use AmeliaBooking\Application\Controller\Coupon\UpdateCouponController;
use AmeliaBooking\Application\Controller\Coupon\UpdateCouponStatusController;
use AmeliaBooking\Application\Controller\Coupon\GetValidCouponController;
use Slim\App;

/**
 * Class Coupon
 *
 * @package AmeliaBooking\Infrastructure\Routes\Coupon
 */
class Coupon
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/coupons', GetCouponsController::class);

        $app->get('/coupons/{id:[0-9]+}', GetCouponController::class);

        $app->post('/coupons', AddCouponController::class);

        $app->post('/coupons/delete/{id:[0-9]+}', DeleteCouponController::class);

        $app->post('/coupons/{id:[0-9]+}', UpdateCouponController::class);

        $app->post('/coupons/status/{id:[0-9]+}', UpdateCouponStatusController::class);

        $app->get('/coupons/validate', GetValidCouponController::class);

        $app->post('/coupons/validate', GetValidCouponController::class);
    }
}
