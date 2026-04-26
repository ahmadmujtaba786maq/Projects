<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class BookingShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class BookingShortcodeService extends AmeliaShortcodeService
{
    /**
     * @param array $atts
     * @return string
     * @throws InvalidArgumentException
     */
    public static function shortcodeHandler($atts)
    {
        $atts = shortcode_atts(
            [
                'trigger'  => '',
                'show'     => '',
                'category' => null,
                'service'  => null,
                'employee' => null,
                'location' => null,
                'counter'  => self::$counter
            ],
            $atts
        );

        self::prepareScriptsAndStyles();

        ob_start();
        include AMELIA_PATH . '/view/frontend/booking.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
