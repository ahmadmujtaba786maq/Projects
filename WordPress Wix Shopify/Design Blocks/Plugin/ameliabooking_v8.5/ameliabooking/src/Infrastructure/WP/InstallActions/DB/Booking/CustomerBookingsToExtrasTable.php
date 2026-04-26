<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CustomerBookingsToExtrasTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class CustomerBookingsToExtrasTable extends AbstractDatabaseTable
{
    public const TABLE = 'customer_bookings_to_extras';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `customerBookingId` INT(11) NOT NULL,
                    `extraId` INT(11) NOT NULL,
                    `quantity` INT(11) NOT NULL,
                    `price` DOUBLE NOT NULL,
                    `tax` VARCHAR(255) DEFAULT NULL,
                    `aggregatedPrice` TINYINT(1) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `bookingExtra` (`customerBookingId` ,`extraId`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
