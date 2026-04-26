<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class NotificationsLogTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 */
class NotificationsLogTable extends AbstractDatabaseTable
{
    public const TABLE = 'notifications_log';

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
                    `userId` INT(11) NULL,
                    `appointmentId` INT(11) NULL,
                    `eventId` INT(11) NULL,
                    `packageCustomerId` INT(11) NULL,
                    `sentDateTime` DATETIME NOT NULL,
                    `sent` TINYINT(1) NULL,
                    `data` TEXT NULL,
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

        return ["ALTER TABLE {$table} MODIFY userId INT(11) NULL"];
    }
}
