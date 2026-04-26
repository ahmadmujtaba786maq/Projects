<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class ProvidersServiceTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersServiceTable extends AbstractDatabaseTable
{
    public const TABLE = 'providers_to_services';

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
                  `serviceId` int(11) NOT NULL,
                  `price` double NOT NULL,
                  `minCapacity` int(11) NOT NULL,
                  `maxCapacity` int(11) NOT NULL,
                  `customPricing` TEXT NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public static function alterTable()
    {
        $table = self::getTableName();

        global $wpdb;

        $duplicatedRowsIds = $wpdb->get_col(
            "SELECT t1.id AS id
            FROM {$table} t1
            INNER JOIN {$table} t2 ON t1.userId = t2.userId AND t1.serviceId = t2.serviceId
            WHERE
                t1.id > t2.id AND
                t1.userId = t2.userId AND
                t1.serviceId = t2.serviceId
            GROUP BY t1.id"
        );

        foreach ($duplicatedRowsIds as $key => $id) {
            $duplicatedRowsIds[$key] = (int)$duplicatedRowsIds[$key];
        }

        if ($duplicatedRowsIds) {
            $duplicatedRowsIdsQuery = implode(', ', $duplicatedRowsIds);

            $wpdb->query("DELETE FROM {$table} WHERE id IN ({$duplicatedRowsIdsQuery})");
        }

        return [];
    }
}
