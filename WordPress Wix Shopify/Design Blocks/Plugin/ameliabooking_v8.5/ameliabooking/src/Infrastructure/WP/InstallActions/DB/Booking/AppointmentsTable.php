<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class AppointmentsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class AppointmentsTable extends AbstractDatabaseTable
{
    public const TABLE = 'appointments';

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
                   `status` ENUM('approved', 'pending', 'canceled', 'rejected', 'no-show') NULL,
                   `bookingStart` DATETIME NOT NULL,
                   `bookingEnd` DATETIME NOT NULL,
                   `notifyParticipants` TINYINT(1) NOT NULL,
                   `createPaymentLinks` TINYINT(1) DEFAULT 1,
                   `serviceId` INT(11) NOT NULL,
                   `packageId` INT(11) DEFAULT NULL,
                   `providerId` INT(11) NOT NULL,
                   `locationId` INT(11) NULL,
                   `internalNotes` TEXT({$description}) NULL,
                   `googleCalendarEventId` VARCHAR(255) NULL,
                   `googleMeetUrl` VARCHAR(255) NULL,
                   `outlookCalendarEventId` VARCHAR(255) NULL,
                   `microsoftTeamsUrl` VARCHAR(255) NULL,
                   `appleCalendarEventId` VARCHAR(255) NULL,
                   `zoomMeeting` TEXT({$description}) NULL,
                   `lessonSpace` TEXT({$description}) NULL,
                   `parentId` INT(11) NULL,
                   `error` TEXT({$description}) DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
