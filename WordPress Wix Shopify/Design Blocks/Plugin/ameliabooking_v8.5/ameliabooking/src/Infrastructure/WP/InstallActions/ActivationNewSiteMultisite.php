<?php

/**
 * Multisite hook on new site activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

/**
 * Class ActivationNewSiteMultisite
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class ActivationNewSiteMultisite
{
    /**
     * Activate the plugin for every newly created site if the plugin is network activated
     *
     * @param $siteId
     */
    public static function init($siteId)
    {
        if (is_plugin_active_for_network(AMELIA_PLUGIN_SLUG)) {
            switch_to_blog($siteId);
            //Create database table if not exists
            ActivationDatabaseHook::init();
            restore_current_blog();
        }
    }
}
