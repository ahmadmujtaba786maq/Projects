<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\GutenbergBlock;

use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\Licence;

/**
 * Class AmeliaEventsListBookingGutenbergBlock
 *
 * @package AmeliaBooking\Infrastructure\WP\GutenbergBlock
 */
class AmeliaEventsListBookingGutenbergBlock extends GutenbergBlock
{
    /**
     * Register Amelia Events block for Gutenberg
     */
    public static function registerBlockType()
    {
        wp_enqueue_script(
            'amelia_events_list_booking_gutenberg_block',
            AMELIA_URL . 'public/js/gutenberg/amelia-events-list-booking/amelia-events-list-booking-gutenberg.js',
            array('wp-blocks', 'wp-components', 'wp-element', 'wp-editor')
        );

        wp_localize_script(
            'amelia_events_list_booking_gutenberg_block',
            'wpAmeliaLabels',
            array_merge(
                BackendStrings::getCommonStrings(),
                BackendStrings::getWordPressStrings(),
                self::getEntitiesData(),
                array('isLite' => !Licence\Licence::$premium)
            )
        );

        wp_enqueue_style(
            'amelia_events_list_booking_gutenberg_styles',
            AMELIA_URL . 'public/js/gutenberg/amelia-events-list-booking/amelia-gutenberg-styles.css',
            [],
            AMELIA_VERSION
        );

        register_block_type(
            'amelia/events-list-booking-gutenberg-block',
            array('editor_script' => 'amelia_events_list_booking_gutenberg_block')
        );
    }
}
