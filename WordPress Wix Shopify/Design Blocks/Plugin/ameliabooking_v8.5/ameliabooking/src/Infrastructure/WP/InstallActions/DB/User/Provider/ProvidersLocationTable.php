<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class ProvidersLocationTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersLocationTable extends AbstractDatabaseTable
{
    public const TABLE = 'providers_to_locations';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `userId` int(11) NOT NULL,
                  `locationId` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
