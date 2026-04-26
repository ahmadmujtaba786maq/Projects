<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CategoriesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class CategoriesTable extends AbstractDatabaseTable
{
    public const TABLE = 'categories';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name    = Name::MAX_LENGTH;
        $color   = Color::MAX_LENGTH;
        $picture = Picture::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `status` enum('hidden', 'visible', 'disabled') NOT NULL default 'visible',
                   `name` varchar ({$name}) NOT NULL default '',
                   `position` int(11) NOT NULL,
                   `translations` TEXT NULL DEFAULT NULL,
                   `color` varchar({$color}) NOT NULL default '#1788FB',
                   `pictureFullPath` varchar ({$picture}) NULL,
                   `pictureThumbPath` varchar ({$picture}) NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
