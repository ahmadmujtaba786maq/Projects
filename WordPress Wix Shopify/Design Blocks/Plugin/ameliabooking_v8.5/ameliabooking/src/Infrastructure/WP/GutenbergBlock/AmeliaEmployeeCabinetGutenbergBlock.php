<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\GutenbergBlock;

/**
 * Class AmeliaEmployeeCabinetGutenbergBlock
 *
 * @package AmeliaBooking\Infrastructure\WP\GutenbergBlock
 */
class AmeliaEmployeeCabinetGutenbergBlock extends GutenbergBlock
{
    /**
     * Register Amelia Employee CabinetGutenberg block for gutenberg
     */
    public static function registerBlockType()
    {
        wp_enqueue_script(
            'amelia_employee_cabinet_gutenberg_block',
            AMELIA_URL . 'public/js/gutenberg/amelia-cabinet/amelia-employee-cabinet-gutenberg.js',
            array('wp-blocks', 'wp-components', 'wp-element', 'wp-editor')
        );

        register_block_type(
            'amelia/employee-cabinet-gutenberg-block',
            array('editor_script' => 'amelia_employee_cabinet_gutenberg_block')
        );
    }
}
