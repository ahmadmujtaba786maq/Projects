<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class NotificationsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 */
class NotificationsToEntitiesTable extends AbstractDatabaseTable
{
    public const TABLE = 'notifications_to_entities';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `notificationId` INT(11) NOT NULL,
                   `entityId` INT(11) NOT NULL,
                   `entity` ENUM('appointment', 'event') NOT NULL DEFAULT 'appointment',
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
