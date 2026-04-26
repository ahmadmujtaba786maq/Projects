<?php

/**
 * Network activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

/**
 * Class DeletionMultisite
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class DeletionMultisite
{
    /**
     * Delete the plugin tables for every sub-site separately
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function delete()
    {
        global $wpdb;

        // Get current blog id
        $oldSite = $wpdb->blogid;
        // Get all blog ids
        $siteIds = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

        foreach ($siteIds as $siteId) {
            switch_to_blog($siteId);
            // Delete database tables if exists
            DeleteDatabaseHook::delete();
        }
        // Returns to current blog
        switch_to_blog($oldSite);
    }
}
