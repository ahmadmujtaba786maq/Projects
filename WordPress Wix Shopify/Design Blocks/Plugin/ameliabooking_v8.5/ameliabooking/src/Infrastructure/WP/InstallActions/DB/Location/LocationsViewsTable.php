<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class LocationsViewsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location
 */
class LocationsViewsTable extends AbstractDatabaseTable
{
    public const TABLE = 'locations_views';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `locationId` INT(11) NOT NULL,
                  `date` DATE NOT NULL,
                  `views` INT(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
