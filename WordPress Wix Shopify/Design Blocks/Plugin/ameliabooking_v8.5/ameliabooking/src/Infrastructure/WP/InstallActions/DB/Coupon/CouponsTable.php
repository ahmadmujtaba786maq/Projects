<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CouponsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon
 */
class CouponsTable extends AbstractDatabaseTable
{
    public const TABLE = 'coupons';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `code` VARCHAR(255) NOT NULL COLLATE utf8_bin,
                   `discount` DOUBLE NOT NULL,
                   `deduction` DOUBLE NOT NULL,
                   `limit` DOUBLE NOT NULL,
                   `customerLimit` DOUBLE NOT NULL DEFAULT 0,
                   `status` ENUM('hidden', 'visible') NOT NULL,
                   `notificationInterval` INT(11) NOT NULL DEFAULT 0,
                   `notificationRecurring` TINYINT(1) NOT NULL DEFAULT 0,
                   `expirationDate` DATETIME NULL,
                   `allServices` TINYINT(1) NOT NULL DEFAULT 0,
                   `allEvents` TINYINT(1) NOT NULL DEFAULT 0,
                   `allPackages` TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
