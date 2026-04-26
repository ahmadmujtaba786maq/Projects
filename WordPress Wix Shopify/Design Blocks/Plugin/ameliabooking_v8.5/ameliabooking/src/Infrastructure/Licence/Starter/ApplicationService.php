<?php

namespace AmeliaBooking\Infrastructure\Licence\Starter;

use AmeliaBooking\Application\Services as ApplicationServices;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class ApplicationService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Starter
 */
class ApplicationService extends \AmeliaBooking\Infrastructure\Licence\Lite\ApplicationService
{
    /**
     * @param Container $c
     *
     * @return ApplicationServices\Coupon\AbstractCouponApplicationService
     */
    public static function getCouponService($c)
    {
        return new ApplicationServices\Coupon\CouponApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Extra\AbstractExtraApplicationService
     */
    public static function getExtraService($c)
    {
        return new ApplicationServices\Extra\ExtraApplicationService($c);
    }

    /**
     * @return ApplicationServices\Location\AbstractCurrentLocation
     */
    public static function getCurrentLocationService()
    {
        return new ApplicationServices\Location\CurrentLocation();
    }
}
