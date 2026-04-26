<?php

namespace AmeliaBooking\Infrastructure\Licence\Basic;

use AmeliaBooking\Application\Services as ApplicationServices;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class ApplicationService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Basic
 */
class ApplicationService extends \AmeliaBooking\Infrastructure\Licence\Starter\ApplicationService
{
    /**
     * @param Container $c
     *
     * @return ApplicationServices\Tax\AbstractTaxApplicationService
     */
    public static function getTaxService($c)
    {
        return new ApplicationServices\Tax\TaxApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Deposit\AbstractDepositApplicationService
     */
    public static function getDepositService($c)
    {
        return new ApplicationServices\Deposit\DepositApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Location\AbstractLocationApplicationService
     */
    public static function getLocationService($c)
    {
        return new ApplicationServices\Location\LocationApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\CustomField\AbstractCustomFieldApplicationService
     */
    public static function getCustomFieldService($c)
    {
        return new ApplicationServices\CustomField\CustomFieldApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\WebHook\AbstractWebHookApplicationService
     */
    public static function getWebHookService($c)
    {
        return new ApplicationServices\WebHook\WebHookApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Zoom\AbstractZoomApplicationService
     */
    public static function getZoomService($c)
    {
        return new ApplicationServices\Zoom\ZoomApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Invoice\AbstractInvoiceApplicationService
     */
    public static function getInvoiceService($c)
    {
        return new ApplicationServices\Invoice\InvoiceApplicationService($c);
    }
}
