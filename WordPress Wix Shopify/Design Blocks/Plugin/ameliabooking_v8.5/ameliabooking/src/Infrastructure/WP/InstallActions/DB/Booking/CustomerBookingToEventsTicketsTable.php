<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CustomerBookingToEventsTicketsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class CustomerBookingToEventsTicketsTable extends AbstractDatabaseTable
{
    public const TABLE = 'customer_bookings_to_events_tickets';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `customerBookingId` bigint(20) NOT NULL,
                    `eventTicketId` bigint(20) NOT NULL,
                    `price` double DEFAULT 0,
                    `persons` int(11) NOT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
