<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class PackagesCustomersTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class PackagesCustomersTable extends AbstractDatabaseTable
{
    public const TABLE = 'packages_to_customers';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $token = Token::MAX_LENGTH;

        return "CREATE TABLE {$table}  (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `packageId` INT(11) NOT NULL,
                  `customerId` INT(11) NOT NULL,
                  `price` DOUBLE NOT NULL,
                  `tax` VARCHAR(255) DEFAULT NULL,
                  `start` DATETIME NULL,
                  `end` DATETIME NULL,
                  `purchased` DATETIME NOT NULL,
                  `status` ENUM('approved', 'pending', 'canceled', 'rejected') DEFAULT NULL,
                  `bookingsCount` INT(5) DEFAULT NULL,
                  `couponId` INT(11) DEFAULT NULL,
                  `token` VARCHAR({$token}) NULL,
                  PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
