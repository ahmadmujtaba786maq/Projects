<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CustomFieldsServicesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField
 */
class CustomFieldsServicesTable extends AbstractDatabaseTable
{
    public const TABLE = 'custom_fields_services';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        return "CREATE TABLE {$table} (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `customFieldId` int(11) NOT NULL,
                  `serviceId` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
