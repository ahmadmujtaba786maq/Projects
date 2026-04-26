<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class NotificationsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 */
class ResourcesToEntitiesTable extends AbstractDatabaseTable
{
    public const TABLE = 'resources_to_entities';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `resourceId` INT(11) NOT NULL,
                   `entityId` INT(11) NOT NULL,
                   `entityType` ENUM('service', 'location', 'employee') NOT NULL DEFAULT 'service',
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
