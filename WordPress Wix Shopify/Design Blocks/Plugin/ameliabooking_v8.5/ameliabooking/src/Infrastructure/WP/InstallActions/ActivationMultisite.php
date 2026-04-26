<?php

/**
 * Network activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

/**
 * Class ActivationMultisite
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class ActivationMultisite
{
    /**
     * Activate the plugin for every sub-site separately
     */
    public static function init()
    {
        global $wpdb;

        // Get current blog id
        $oldSite = $wpdb->blogid;
        // Get all blog ids
        $siteIds = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach ($siteIds as $siteId) {
            switch_to_blog($siteId);
            // Create database table if not exists
            ActivationDatabaseHook::init();
        }
        // Returns to current blog
        switch_to_blog($oldSite);
    }
}
