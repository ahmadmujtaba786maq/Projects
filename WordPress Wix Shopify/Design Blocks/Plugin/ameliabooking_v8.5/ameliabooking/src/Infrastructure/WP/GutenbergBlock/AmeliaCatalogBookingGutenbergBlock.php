<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\GutenbergBlock;

use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class AmeliaCatalogBookingGutenbergBlock
 *
 * @package AmeliaBooking\Infrastructure\WP\GutenbergBlock
 */
class AmeliaCatalogBookingGutenbergBlock extends GutenbergBlock
{
    /**
     * Register Amelia Booking block for Gutenberg
     */
    public static function registerBlockType()
    {
        wp_enqueue_script(
            'amelia_catalog_booking_gutenberg_block',
            AMELIA_URL . 'public/js/gutenberg/amelia-catalog-booking/amelia-catalog-booking-gutenberg.js',
            array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-editor')
        );

        wp_localize_script(
            'amelia_catalog_booking_gutenberg_block',
            'wpAmeliaLabels',
            array_merge(
                BackendStrings::getCommonStrings(),
                BackendStrings::getWordPressStrings(),
                self::getEntitiesData()
            )
        );


        wp_enqueue_style(
            'amelia_catalog_booking_gutenberg_styles',
            AMELIA_URL . 'public/js/gutenberg/amelia-catalog-booking/amelia-catalog-booking-gutenberg.css',
            [],
            AMELIA_VERSION
        );

        register_block_type(
            'amelia/catalog-booking-gutenberg-block',
            array('editor_script' => 'amelia_catalog_booking_gutenberg_block')
        );
    }
}
