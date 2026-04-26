<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

class ResourcesTable extends AbstractDatabaseTable
{
    public const TABLE = 'resources';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name = Name::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `name` varchar({$name}) NOT NULL DEFAULT '',
                   `quantity` INT(11) DEFAULT 1,
                   `shared` ENUM('service', 'location') DEFAULT NULL,
                   `status` ENUM('hidden', 'visible', 'disabled') NOT NULL DEFAULT 'visible',
                   `countAdditionalPeople` TINYINT(1) DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
