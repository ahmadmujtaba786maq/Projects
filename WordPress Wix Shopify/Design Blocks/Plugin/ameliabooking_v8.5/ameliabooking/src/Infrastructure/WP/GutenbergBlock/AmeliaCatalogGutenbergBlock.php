<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\GutenbergBlock;

/**
 * Class AmeliaCatalogGutenbergBlock
 *
 * @package AmeliaBooking\Infrastructure\WP\GutenbergBlock
 */
class AmeliaCatalogGutenbergBlock extends GutenbergBlock
{
    /**
     * Register Amelia Catalog block for gutenberg
     */
    public static function registerBlockType()
    {
        wp_enqueue_script(
            'amelia_catalog_gutenberg_block',
            AMELIA_URL . 'public/js/gutenberg/amelia-catalog/amelia-catalog-gutenberg.js',
            array('wp-blocks', 'wp-components', 'wp-element', 'wp-editor')
        );

        wp_enqueue_style(
            'amelia_catalog_gutenberg_styles',
            AMELIA_URL . 'public/js/gutenberg/amelia-catalog/amelia-catalog-gutenberg.css',
            [],
            AMELIA_VERSION
        );

        register_block_type(
            'amelia/catalog-gutenberg-block',
            array('editor_script' => 'amelia_catalog_gutenberg_block')
        );
    }
}
