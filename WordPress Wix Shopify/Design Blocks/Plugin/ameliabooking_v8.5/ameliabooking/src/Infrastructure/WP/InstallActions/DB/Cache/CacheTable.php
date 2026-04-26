<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Cache;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CacheTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Cache
 */
class CacheTable extends AbstractDatabaseTable
{
    public const TABLE = 'cache';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `name` VARCHAR(255) NOT NULL,
                   `paymentId` INT(11) DEFAULT NULL,
                   `data` TEXT NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
