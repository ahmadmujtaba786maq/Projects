<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CouponsToPackagesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon
 */
class CouponsToPackagesTable extends AbstractDatabaseTable
{
    public const TABLE = 'coupons_to_packages';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `couponId` int(11) NOT NULL,
                   `packageId` int(11) NOT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
