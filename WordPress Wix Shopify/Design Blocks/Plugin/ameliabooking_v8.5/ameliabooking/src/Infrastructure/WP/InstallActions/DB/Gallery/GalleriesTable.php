<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Gallery;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class GalleriesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Gallery
 */
class GalleriesTable extends AbstractDatabaseTable
{
    public const TABLE = 'galleries';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $picture = Picture::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `entityId` int(11) NOT NULL,
                   `entityType` ENUM('service', 'event', 'package') NOT NULL,
                   `pictureFullPath` varchar ({$picture}) NULL,
                   `pictureThumbPath` varchar ({$picture}) NULL,
                   `position` int(11) NOT NULL,
                    PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
