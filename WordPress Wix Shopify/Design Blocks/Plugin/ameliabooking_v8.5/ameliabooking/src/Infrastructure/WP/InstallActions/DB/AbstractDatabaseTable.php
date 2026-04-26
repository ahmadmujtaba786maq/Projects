<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class AbstractDatabaseTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB
 */
class AbstractDatabaseTable
{
    public const TABLE = '';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getTableName()
    {
        if (!static::TABLE) {
            throw new InvalidArgumentException('Table name is not provided');
        }

        global $wpdb;
        return $wpdb->prefix . 'amelia_' . static::TABLE;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        return '';
    }

    /**
     * Create new table in the database
     */
    public static function init()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(static::buildTable());

        global $wpdb;

        foreach (static::alterTable() as $command) {
            $wpdb->query($command);
        }
    }

    /**
     * Delete table table from the database
     *
     * @throws InvalidArgumentException
     */
    public static function delete()
    {
        global $wpdb;

        $table = self::getTableName();

        $sql = "DROP TABLE IF EXISTS {$table};";
        $wpdb->query($sql);
    }

    /**
     * @return boolean
     */
    public static function isValidTablePrefix()
    {
        global $wpdb;

        return strlen($wpdb->prefix) <= 16;
    }

    /**
     * @return array
     */
    public static function alterTable()
    {
        return [];
    }
}
