<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;

/**
 * Class WPUsersTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User
 */
class WPUsersTable extends AbstractDatabaseTable
{
    public const TABLE = 'users';

    public const META_TABLE = 'usermeta';

    /**
     * @return string
     */
    public static function getTableName()
    {
        return self::getDatabaseBasePrefix() . static::TABLE;
    }

    /**
     * @return string
     */
    public static function getMetaTableName()
    {
        return self::getDatabaseBasePrefix() . static::META_TABLE;
    }

    /**
     * @return string
     */
    public static function getDatabasePrefix()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $prefix = $settingsService->getSetting('db', 'wpTablesPrefix');

        global $wpdb;
        return !$prefix ? $wpdb->prefix : $prefix;
    }

    /**
     * @return string
     */
    public static function getDatabaseBasePrefix()
    {
        $settingsService = new SettingsService(new SettingsStorage());

        $prefix = $settingsService->getSetting('db', 'wpTablesPrefix');

        global $wpdb;
        return !$prefix ? $wpdb->base_prefix : $prefix;
    }
}
