<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class PackagesCustomersServicesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class PackagesCustomersServicesTable extends AbstractDatabaseTable
{
    public const TABLE = 'packages_customers_to_services';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `packageCustomerId` INT(11) NOT NULL,
                    `serviceId` INT(11) NOT NULL,
                    `providerId` INT(11) NULL,
                    `locationId` INT(11) NULL,
                    `bookingsCount` INT(5) DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public static function alterTable()
    {
        $table = self::getTableName();

        $usersTable = UsersTable::getTableName();

        global $wpdb;

        $deletedProviderIds = $wpdb->get_col(
            "SELECT t1.providerId FROM {$table} t1
            WHERE t1.providerId NOT IN (SELECT t2.id FROM {$usersTable} t2)"
        );

        foreach ($deletedProviderIds as $key => $id) {
            $deletedProviderIds[$key] = (int)$deletedProviderIds[$key];
        }

        if ($deletedProviderIds) {
            $deletedProvidersIdsQuery = implode(', ', $deletedProviderIds);

            $wpdb->query("UPDATE {$table} SET providerId = NULL WHERE providerId IN ({$deletedProvidersIdsQuery})");
        }

        return [];
    }
}
