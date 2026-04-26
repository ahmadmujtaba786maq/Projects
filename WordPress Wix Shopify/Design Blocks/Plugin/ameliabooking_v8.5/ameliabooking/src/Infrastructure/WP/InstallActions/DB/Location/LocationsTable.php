<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Address;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Phone;
use AmeliaBooking\Domain\ValueObjects\String\Url;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class LocationsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location
 */
class LocationsTable extends AbstractDatabaseTable
{
    public const TABLE = 'locations';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name        = Name::MAX_LENGTH;
        $description = Description::MAX_LENGTH;
        $address     = Address::MAX_LENGTH;
        $phone       = Phone::MAX_LENGTH;
        $picture     = Picture::MAX_LENGTH;
        $url         = Url::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `status` ENUM('hidden', 'visible', 'disabled') NOT NULL default 'visible',
                    `name` varchar ({$name}) NOT NULL default '',
                    `description` text({$description}) NULL,
                    `address` varchar ({$address}) NOT NULL,
                    `phone` varchar ({$phone}) NOT NULL,
                    `latitude` decimal(8, 6) NOT NULL,
                    `longitude` decimal(9, 6) NOT NULL,
                    `pictureFullPath` varchar ({$picture}) NULL,
                    `pictureThumbPath` varchar ({$picture}) NULL,
                    `pin` varchar ({$url}) NULL,
                    `translations` TEXT NULL DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
