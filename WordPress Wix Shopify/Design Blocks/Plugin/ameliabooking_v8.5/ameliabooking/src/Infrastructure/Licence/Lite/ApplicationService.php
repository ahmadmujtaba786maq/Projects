<?php

namespace AmeliaBooking\Infrastructure\Licence\Lite;

use AmeliaBooking\Application\Services as ApplicationServices;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class ApplicationService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Lite
 */
class ApplicationService
{
    /**
     * @param Container $c
     *
     * @return ApplicationServices\User\UserApplicationService
     */
    public static function getApiService($c)
    {
        return new ApplicationServices\User\UserApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Deposit\AbstractDepositApplicationService
     */
    public static function getDepositService($c)
    {
        return new ApplicationServices\Deposit\StarterDepositApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Tax\AbstractTaxApplicationService
     */
    public static function getTaxService($c)
    {
        return new ApplicationServices\Tax\StarterTaxApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Coupon\AbstractCouponApplicationService
     */
    public static function getCouponService($c)
    {
        return new ApplicationServices\Coupon\LiteCouponApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Extra\AbstractExtraApplicationService
     */
    public static function getExtraService($c)
    {
        return new ApplicationServices\Extra\LiteExtraApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Location\AbstractLocationApplicationService
     */
    public static function getLocationService($c)
    {
        return new ApplicationServices\Location\BasicLocationApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\CustomField\AbstractCustomFieldApplicationService
     */
    public static function getCustomFieldService($c)
    {
        return new ApplicationServices\CustomField\StarterCustomFieldApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\WebHook\AbstractWebHookApplicationService
     */
    public static function getWebHookService($c)
    {
        return new ApplicationServices\WebHook\StarterWebHookApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Zoom\AbstractZoomApplicationService
     */
    public static function getZoomService($c)
    {
        return new ApplicationServices\Zoom\StarterZoomApplicationService($c);
    }

    /**
     * @return ApplicationServices\Location\AbstractCurrentLocation
     */
    public static function getCurrentLocationService()
    {
        return new ApplicationServices\Location\LiteCurrentLocation();
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Bookable\AbstractPackageApplicationService
     */
    public static function getPackageService($c)
    {
        return new ApplicationServices\Bookable\BasicPackageApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Resource\AbstractResourceApplicationService
     */
    public static function getResourceService($c)
    {
        return new ApplicationServices\Resource\BasicResourceApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Notification\AbstractWhatsAppNotificationService
     */
    public static function getWhatsAppNotificationService($c)
    {
        return new ApplicationServices\Notification\BasicWhatsAppNotificationService($c, 'whatsapp');
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Invoice\AbstractInvoiceApplicationService
     */
    public static function getInvoiceService($c)
    {
        return new ApplicationServices\Invoice\StarterInvoiceApplicationService($c);
    }
}
