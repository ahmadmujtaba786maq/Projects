<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class CabinetCustomerShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class CabinetCustomerShortcodeService
{
    /**
     * @param array $atts
     * @return string
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public static function shortcodeHandler($atts)
    {
        if (empty($atts['version']) || $atts['version'] === '1') {
            $atts = shortcode_atts(
                [
                    'trigger'      => '',
                    'counter'      => AmeliaShortcodeService::$counter,
                    'appointments' => null,
                    'events'       => null,
                    'version'      => null,
                ],
                $atts
            );
            AmeliaShortcodeService::prepareScriptsAndStyles();

            // Enqueue Styles
            wp_enqueue_style(
                'amelia_booking_styles_quill',
                AMELIA_URL . 'public/css/frontend/quill.css',
                [],
                AMELIA_VERSION
            );
        } else {
            $atts = shortcode_atts(
                [
                    'trigger'      => '',
                    'counter'      => AmeliaBookingShortcodeService::$counter,
                    'appointments' => null,
                    'events'       => null,
                    'version'      => null,
                ],
                $atts
            );
            AmeliaBookingShortcodeService::prepareScriptsAndStyles();
        }

        ob_start();
        include AMELIA_PATH . '/view/frontend/cabinet-customer.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
