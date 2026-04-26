<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class EventsTicketsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class EventsTicketsTable extends AbstractDatabaseTable
{
    public const TABLE = 'events_to_tickets';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name = Name::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `eventId` bigint(20) NOT NULL,
                   `enabled` TINYINT(1) DEFAULT 1,
                   `name` varchar({$name}) NOT NULL,
                   `price` double DEFAULT 0,
                   `dateRanges` TEXT NULL DEFAULT NULL,
                   `spots` int(11) NOT NULL,
                   `waitingListSpots` int(11) NOT NULL,
                   `translations` TEXT NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
