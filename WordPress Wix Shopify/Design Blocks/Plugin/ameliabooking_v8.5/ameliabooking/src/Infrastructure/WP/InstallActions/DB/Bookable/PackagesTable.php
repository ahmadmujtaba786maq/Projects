<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class PackagesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class PackagesTable extends AbstractDatabaseTable
{
    public const TABLE = 'packages';

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
        $picture     = Picture::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `name` VARCHAR({$name}) NOT NULL DEFAULT '',
                   `description` TEXT({$description}) NULL,
                   `color` VARCHAR({$color}) NOT NULL DEFAULT '',
                   `price` DOUBLE NOT NULL,
                   `status` ENUM('hidden', 'visible', 'disabled') NOT NULL DEFAULT 'visible',
                   `pictureFullPath` VARCHAR ({$picture}) NULL,
                   `pictureThumbPath` VARCHAR ({$picture}) NULL,
                   `position` INT(11) DEFAULT 0,
                   `calculatedPrice` TINYINT(1) DEFAULT 1,
                   `discount` DOUBLE NOT NULL,
                   `endDate` DATETIME NULL,
                   `durationType` ENUM('day', 'week', 'month') DEFAULT NULL,
                   `durationCount` INT(4) DEFAULT NULL,
                   `settings` TEXT({$description}) NULL DEFAULT NULL,
                   `translations` TEXT NULL DEFAULT NULL,
                   `depositPayment` ENUM('disabled' , 'fixed', 'percentage') DEFAULT 'disabled',
                   `deposit` DOUBLE DEFAULT 0,
                   `fullPayment` TINYINT(1) DEFAULT 0,
                   `sharedCapacity` TINYINT(1) DEFAULT 0,
                   `quantity` INT(11) DEFAULT 1,
                   `limitPerCustomer` TEXT NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
