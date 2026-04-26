<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class PackagesServicesLocationsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class PackagesServicesLocationsTable extends AbstractDatabaseTable
{
    public const TABLE = 'packages_services_to_locations';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `packageServiceId` INT(11) NOT NULL,
                  `locationId` INT(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
