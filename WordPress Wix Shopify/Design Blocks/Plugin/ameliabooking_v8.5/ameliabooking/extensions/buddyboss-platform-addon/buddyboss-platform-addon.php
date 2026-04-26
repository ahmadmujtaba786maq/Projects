<?php
/**
 * Plugin Name: Amelia
 * Plugin URI:  https://wpamelia.com/
 * Description: Amelia integration with BuddyBoss Platform
 * Author:      TMS
 * Author URI:  https://tmsproducts.io/
 * Version:     1.0.0
 * Text Domain: buddyboss-platform-addon
 * Domain Path: /languages/
 * License:     GPLv3 or later (license.txt)
 */

/**
 * This file should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

if (! class_exists('Amelia_BB_Platform_Addon')) {

    /**
     * Main Amelia Custom Emails Class
     *
     * @class Amelia_BB_Platform_Addon
     * @version 1.0.0
     */
    final class Amelia_BB_Platform_Addon
    {

        /**
         * @var Amelia_BB_Platform_Addon The single instance of the class
         * @since 1.0.0
         */
        protected static $_instance = null;

        /**
         * Main Amelia_BB_Platform_Addon Instance
         *
         * Ensures only one instance of Amelia_BB_Platform_Addon is loaded or can be loaded.
         *
         * @since 1.0.0
         * @static
         * @see Amelia_BB_Platform_Addon()
         * @return Amelia_BB_Platform_Addon - Main instance
         */
        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Cloning is forbidden.
         * @since 1.0.0
         */
        public function __clone()
        {
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'buddyboss-platform-addon'), '1.0.0');
        }
        /**
         * Unserializing instances of this class is forbidden.
         * @since 1.0.0
         */
        public function __wakeup()
        {
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'buddyboss-platform-addon'), '1.0.0');
        }

        /**
         * Amelia_BB_Platform_Addon Constructor.
         */
        public function __construct()
        {
            global $bp;
            $this->define_constants();
            $this->includes();
            // Set up localisation.
//          $this->load_plugin_textdomain();
        }

        /**
         * Define WCE Constants
         */
        private function define_constants()
        {
            $this->define('Amelia_BB_ADDON_PLUGIN_FILE', __FILE__);
            $this->define('Amelia_BB_ADDON_PLUGIN_BASENAME', plugin_basename(__FILE__));
            $this->define('Amelia_BB_ADDON_PLUGIN_PATH', plugin_dir_path(__FILE__));
            $this->define('Amelia_BB_ADDON_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        /**
         * Define constant if not already set
         * @param  string $name
         * @param  string|bool $value
         */
        private function define($name, $value)
        {
            if (! defined($name)) {
                define($name, $value);
            }
        }

        /**
         * Include required core files used in admin and on the frontend.
         */
        public function includes()
        {
            include_once(AMELIA_PATH . '/extensions/buddyboss-platform-addon/functions.php');
        }

        /**
         * Get the plugin url.
         * @return string
         */
        public function plugin_url()
        {
            return untrailingslashit(plugins_url('/', __FILE__));
        }

        /**
         * Get the plugin path.
         * @return string
         */
        public function plugin_path()
        {
            return untrailingslashit(plugin_dir_path(__FILE__));
        }

        /**
         * Load Localisation files.
         *
         * Note: the first-loaded translation file overrides any following ones if the same translation is present.
         */
        public function load_plugin_textdomain()
        {
            $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
            $locale = apply_filters('plugin_locale', $locale, 'buddyboss-platform-addon');

            unload_textdomain('buddyboss-platform-addon');
            load_textdomain('buddyboss-platform-addon', WP_LANG_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/' . plugin_basename(dirname(__FILE__)) . '-' . $locale . '.mo');
            load_plugin_textdomain('buddyboss-platform-addon', false, plugin_basename(dirname(__FILE__)) . '/languages');
        }
    }

    /**
     * Returns the main instance of Amelia_BB_Platform_Addon to prevent the need to use globals.
     *
     * @since  1.0.0
     * @return Amelia_BB_Platform_Addon
     */
    function Amelia_BB_Platform_Addon()
    {
        return Amelia_BB_Platform_Addon::instance();
    }

    function Amelia_BB_Platform_install_bb_platform_notice()
    {
        echo '<div class="error fade"><p>';
        _e('<strong>BuddyBoss Platform Add-on</strong></a> requires the BuddyBoss Platform plugin to work. Please <a href="https://buddyboss.com/platform/" target="_blank">install BuddyBoss Platform</a> first.', 'buddyboss-platform-addon');
        echo '</p></div>';
    }

    function Amelia_BB_Platform_update_bb_platform_notice()
    {
        echo '<div class="error fade"><p>';
        _e('<strong>BuddyBoss Platform Add-on</strong></a> requires BuddyBoss Platform plugin version 1.2.6 or higher to work. Please update BuddyBoss Platform.', 'buddyboss-platform-addon');
        echo '</p></div>';
    }

    function Amelia_BB_Platform_is_active()
    {
        if (defined('BP_PLATFORM_VERSION') && version_compare(BP_PLATFORM_VERSION, '1.2.6', '>=')) {
            return true;
        }
        return false;
    }

    function Amelia_BB_Platform_init()
    {
        if (! defined('BP_PLATFORM_VERSION')) {
            return;
        }

        if (version_compare(BP_PLATFORM_VERSION, '1.2.6', '<')) {
            return;
        }

        Amelia_BB_Platform_Addon();
    }

    Amelia_BB_Platform_init();
}
