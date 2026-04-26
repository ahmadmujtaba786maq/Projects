<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class EventsShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class EventsShortcodeService extends AmeliaShortcodeService
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
                'trigger'       => '',
                'counter'       => self::$counter,
                'event'         => null,
                'recurring'     => null,
                'employee'      => null,
                'tag'           => null,
                'today'         => null,
                'type'          => null,
            ],
            $atts
        );

        if (!empty($atts['tag'])) {
            $atts['tag'] = htmlspecialchars_decode($atts['tag']);
        }

        self::prepareScriptsAndStyles();

        ob_start();
        include AMELIA_PATH . '/view/frontend/events.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
