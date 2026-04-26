<?php

namespace AmeliaBooking\Infrastructure\Licence\Pro;

use AmeliaBooking\Application\Services as ApplicationServices;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class ApplicationService
 *
 * @package AmeliaBooking\Infrastructure\Licence\Pro
 */
class ApplicationService extends \AmeliaBooking\Infrastructure\Licence\Basic\ApplicationService
{
    /**
     * @param Container $c
     *
     * @return ApplicationServices\Bookable\AbstractPackageApplicationService
     */
    public static function getPackageService($c)
    {
        return new ApplicationServices\Bookable\PackageApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Resource\AbstractResourceApplicationService
     */
    public static function getResourceService($c)
    {
        return new ApplicationServices\Resource\ResourceApplicationService($c);
    }

    /**
     * @param Container $c
     *
     * @return ApplicationServices\Notification\AbstractWhatsAppNotificationService
     */
    public static function getWhatsAppNotificationService($c)
    {
        return new ApplicationServices\Notification\WhatsAppNotificationService($c, 'whatsapp');
    }
}
