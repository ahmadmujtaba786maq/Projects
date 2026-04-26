<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

if (! function_exists('amelia_installed_plugins_wpdt_promotion')) :
    function amelia_installed_plugins_wpdt_promotion()
    {
        $plugins_to_check = array(
            'visualizer/index.php',
            'tablepress/tablepress.php',
            'ninja-tables/ninja-tables.php',
            'wp-table-builder/wp-table-builder.php',
            'wp-table-manager/wp-table-manager.php',
            'data-tables-generator-by-supsystic/index.php',
            'superb-tables/superb-tables.php',
            'tablesome/tablesome.php',
            'tableberg/tableberg.php'
        );

        $installed_plugins = array();
        $installed_plugins_not_active = array();

        foreach ($plugins_to_check as $plugin) {
            if (is_plugin_active($plugin)) {
                $installed_plugins[] = $plugin;
            }
            if(wpAmelia_is_plugin_installed($plugin)){
                $installed_plugins_not_active[] = $plugin;
            }
        }

        if (!empty($installed_plugins)) {
            if(!is_plugin_inactive('wpdatatables/wpdatatables.php') &&
                is_plugin_inactive('wpdatatables/wpdatatables.php')) {
                return true;
            }
        }

        if (!empty($installed_plugins_not_active)) {
            if(!wpAmelia_is_plugin_installed('wpdatatables/wpdatatables.php')) {
                return true;
            }
        }

        return false;
    }

    function wpAmelia_is_plugin_installed($plugin_path) {
        $plugins_dir = WP_PLUGIN_DIR;
        $plugin_file = $plugins_dir . '/' . $plugin_path;

        if (file_exists($plugin_file)) {
            return true;
        } else {
            return false;
        }
    }
endif;

?>
