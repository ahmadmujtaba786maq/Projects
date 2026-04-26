<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Domain\ValueObjects\String\Description;

/**
 * Class EventsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class EventsTable extends AbstractDatabaseTable
{
    public const TABLE = 'events';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name        = Name::MAX_LENGTH;
        $description = Description::MAX_LENGTH;
        $color       = Color::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `parentId` bigint(20),
                   `name` varchar({$name}) NOT NULL default '',
                   `status` ENUM('approved','pending','canceled','rejected') NOT NULL,
                   `bookingOpens` DATETIME NULL,
                   `bookingCloses` DATETIME NULL,
                   `bookingOpensRec` ENUM('same', 'calculate') DEFAULT 'same',
                   `bookingClosesRec` ENUM('same', 'calculate') DEFAULT 'same',
                   `ticketRangeRec` ENUM('same', 'calculate') DEFAULT 'calculate',
                   `recurringCycle` ENUM('daily', 'weekly', 'monthly', 'yearly') NULL,
                   `recurringOrder` int(11) NULL,
                   `recurringInterval` int(11) DEFAULT 1,
                   `recurringMonthly` ENUM('each' , 'on') DEFAULT 'each',
                   `monthlyDate` DATETIME NULL,
                   `monthlyOnRepeat` ENUM('first', 'second', 'third', 'fourth', 'fifth', 'last') DEFAULT NULL,
                   `monthlyOnDay` ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') DEFAULT NULL,
                   `recurringUntil` DATETIME NULL,
                   `maxCapacity` int(11) NOT NULL,
                   `maxCustomCapacity` int(11) NULL DEFAULT NULL,
                   `maxExtraPeople` int(11) NULL DEFAULT NULL,
                   `price` double NOT NULL,
                   `locationId` bigint(20) NULL,
                   `customLocation` VARCHAR({$name}) NULL,
                   `description` TEXT({$description}) NULL,
                   `color` varchar({$color}) NULL NULL,
                   `show` TINYINT(1) NOT NULL DEFAULT 1,
                   `notifyParticipants` TINYINT(1) NOT NULL,
                   `created` DATETIME NOT NULL,
                   `settings` text({$description}) NULL DEFAULT NULL,
                   `zoomUserId` varchar({$name}) DEFAULT NULL,
                   `bringingAnyone` TINYINT(1) NULL DEFAULT 1,
                   `bookMultipleTimes` TINYINT(1) NULL DEFAULT 1,
                   `translations` TEXT NULL DEFAULT NULL,
                   `depositPayment` ENUM('disabled' , 'fixed', 'percentage') DEFAULT 'disabled',
                   `depositPerPerson` TINYINT(1) DEFAULT 1,
                   `fullPayment` TINYINT(1) DEFAULT 0,
                   `deposit` double DEFAULT 0,
                   `customPricing` TINYINT(1) DEFAULT 0,
                   `organizerId` bigint(20) NULL,
                   `closeAfterMin` INT(11) NULL DEFAULT NULL,
                   `closeAfterMinBookings` TINYINT(1) DEFAULT 0,
                   `aggregatedPrice` TINYINT(1) DEFAULT 1,
                   `error` TEXT({$description}) DEFAULT NULL,
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

        return ["ALTER TABLE {$table} MODIFY recurringInterval int(11) DEFAULT 1"];
    }
}
