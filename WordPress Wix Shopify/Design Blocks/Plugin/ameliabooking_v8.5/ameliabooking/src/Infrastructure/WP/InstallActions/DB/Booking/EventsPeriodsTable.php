<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class EventsPeriodsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class EventsPeriodsTable extends AbstractDatabaseTable
{
    public const TABLE = 'events_periods';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $description = Description::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `eventId` bigint(20) NOT NULL,
                   `periodStart` DATETIME NOT NULL,
                   `periodEnd` DATETIME NOT NULL,
                   `zoomMeeting` TEXT({$description}) NULL,
                   `lessonSpace` TEXT({$description}) NULL,
                   `googleCalendarEventId` VARCHAR(255) NULL,
                   `googleMeetUrl` VARCHAR(255) NULL,
                   `outlookCalendarEventId` VARCHAR(255) NULL,
                   `microsoftTeamsUrl` VARCHAR(255) NULL,
                   `appleCalendarEventId` VARCHAR(255) NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
