<?php

namespace AmeliaBooking\Infrastructure\WP\ErrorService;

use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class ErrorService
 *
 * @package AmeliaBooking\Infrastructure\WP\ErrorService
 */
class ErrorService
{
    /**
     * Set Notice
     */
    public static function setNotices()
    {
        // Add notice if database prefix is too long
        if (!AbstractDatabaseTable::isValidTablePrefix()) {
            add_action(
                'admin_notices',
                function () {
                    $class   = 'notice notice-error is-dismissible';
                    $message = '<h3>Amelia</h3>
                    <p>Maximum allowed database prefix is 16 characters.</p>
                    <p>Please change the database prefix, deactivate and activate plugin again.</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>';

                    printf('<div class="%1$s">%2$s</div>', $class, $message);
                }
            );
        }
    }
}
