<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\GutenbergBlock;

/**
 * Class AmeliaSearchGutenbergBlock
 *
 * @package AmeliaBooking\Infrastructure\WP\GutenbergBlock
 */
class AmeliaSearchGutenbergBlock extends GutenbergBlock
{
    /**
     * Register Amelia Search block for gutenberg
     */
    public static function registerBlockType()
    {
        wp_enqueue_script(
            'amelia_search_gutenberg_block',
            AMELIA_URL . 'public/js/gutenberg/amelia-search/amelia-search-gutenberg.js',
            array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-editor')
        );

        wp_enqueue_style(
            'amelia_search_gutenberg_styles',
            AMELIA_URL . 'public/js/gutenberg/amelia-search/amelia-search-gutenberg.css',
            [],
            AMELIA_VERSION
        );

        register_block_type(
            'amelia/search-gutenberg-block',
            array('editor_script' => 'amelia_search_gutenberg_block')
        );
    }
}
