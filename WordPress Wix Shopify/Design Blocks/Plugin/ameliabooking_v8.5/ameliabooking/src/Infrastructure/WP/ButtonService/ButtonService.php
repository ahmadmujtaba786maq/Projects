<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ButtonService;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\HelperService\HelperService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class ButtonService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class ButtonService
{
    /**
     * Function that adds shortcode button to WordPress TinyMCE editor on Page and Post pages
     */
    public static function renderButton()
    {
        global $pagenow;

        if (is_admin() && isset($pagenow) && 'customize.php' === $pagenow) {
            return;
        }

        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        if (get_user_option('rich_editing') !== 'true') {
            return;
        }

        add_filter('mce_external_plugins', [self::class, 'addButton']);
        add_filter('mce_buttons', [self::class, 'registerButton']);
        // Export JS variables for classic editor
        add_action('after_wp_tiny_mce', [self::class, 'exportVars']);
        // Export JS variables for Gutenberg editor
        add_action('admin_enqueue_scripts', [self::class, 'exportVars']);
    }

    /**
     * Function that add buttons for MCE editor
     *
     * @param $pluginArray
     *
     * @return mixed
     */
    public static function addButton($pluginArray)
    {
        $pluginArray['ameliaBookingPlugin'] = AMELIA_URL . 'public/js/tinymce/amelia-mce.js';

        return $pluginArray;
    }

    /**
     * Function that register buttons for MCE editor
     *
     * @param $buttons
     *
     * @return mixed
     */
    public static function registerButton($buttons)
    {
        $buttons[] = 'ameliaButton';

        return $buttons;
    }

    public static function exportVars()
    {
        HelperService::exportJSVar('wpAmeliaPluginURL', AMELIA_URL);

        wp_enqueue_script('wp-deactivation-amelia', AMELIA_URL . 'public/js/plugins/delete-plugin.js', []);

        wp_enqueue_style('amelia_booking_tinymce_styles', AMELIA_URL . 'public/js/tinymce/amelia-tinymce-styles.css', array(), AMELIA_VERSION);

        $settingsService = new SettingsService(new SettingsStorage());

        HelperService::exportJSVar(
            'wpAmeliaDeleteSettings',
            $settingsService->getSetting('activation', 'deleteTables') ? 1 : 0
        );

        HelperService::exportJSVar(
            'wpAmeliaNonce',
            wp_create_nonce('ajax-nonce')
        );

        HelperService::exportJSVar('wpAmeliaActionURL', AMELIA_ACTION_URL);

        HelperService::exportJSVar(
            'wpAmeliaLabels',
            array_merge(
                BackendStrings::getCommonStrings(),
                BackendStrings::getWordPressStrings()
            )
        );

        HelperService::printJSVars();
    }
}
