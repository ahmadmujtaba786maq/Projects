<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CustomerBookingsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class CustomerBookingsTable extends AbstractDatabaseTable
{
    public const TABLE = 'customer_bookings';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $token = Token::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `appointmentId` INT(11) NULL,
                    `customerId` INT(11) NOT NULL,
                    `status` ENUM('approved', 'pending', 'canceled', 'rejected', 'no-show', 'waiting') NULL,
                    `price` DOUBLE NOT NULL,
                    `tax` VARCHAR(255) DEFAULT NULL,
                    `persons` INT(11) NOT NULL,
                    `couponId` INT(11) NULL,
                    `token` VARCHAR({$token}) NULL,
                    `customFields` TEXT NULL,
                    `info` TEXT NULL,
                    `utcOffset` INT(3) NULL,
                    `aggregatedPrice` TINYINT(1) DEFAULT 1,
                    `packageCustomerServiceId` INT(11) NULL,
                    `duration` int(11) DEFAULT NULL,
                    `created` DATETIME NULL,
                    `actionsCompleted` TINYINT(1) NULL,
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

        global $wpdb;

        return ($wpdb->get_var("SHOW COLUMNS FROM `{$table}` LIKE 'eventId'") !== 'eventId') ?
            [
                "ALTER TABLE {$table} MODIFY appointmentId INT(11) NULL",
            ] : [];
    }
}
