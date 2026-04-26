<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class SearchShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class SearchShortcodeService extends AmeliaShortcodeService
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
                'trigger' => '',
                'show'    => '',
                'counter' => self::$counter,
                'today'   => null,
            ],
            $atts
        );

        self::prepareScriptsAndStyles();

        ob_start();
        include AMELIA_PATH . '/view/frontend/search.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
