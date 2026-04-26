<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class EventsTagsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class EventsTagsTable extends AbstractDatabaseTable
{
    public const TABLE = 'events_tags';

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
                   `name` varchar({$name}) NOT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
