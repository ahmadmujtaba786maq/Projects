<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class PackagesServicesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class PackagesServicesTable extends AbstractDatabaseTable
{
    public const TABLE = 'packages_to_services';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `serviceId` INT(11) NOT NULL,
                  `packageId` INT(11) NOT NULL,
                  `quantity` INT(11) NOT NULL,
                  `minimumScheduled` INT(5) DEFAULT 1,
                  `maximumScheduled` INT(5) DEFAULT 1,
                  `allowProviderSelection` TINYINT(1) DEFAULT 1,
                  `position` INT(11) DEFAULT 0,
                  PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
