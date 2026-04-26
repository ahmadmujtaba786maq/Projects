<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class ProvidersDayOffTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersDayOffTable extends AbstractDatabaseTable
{
    public const TABLE = 'providers_to_daysoff';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name = Name::MAX_LENGTH;

        return "CREATE TABLE {$table}  (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `userId` int(11) NOT NULL,
                  `name` varchar({$name}) NOT NULL,
                  `startDate` date NOT NULL,
                  `endDate` date NOT NULL,
                  `repeat` tinyint(1) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
