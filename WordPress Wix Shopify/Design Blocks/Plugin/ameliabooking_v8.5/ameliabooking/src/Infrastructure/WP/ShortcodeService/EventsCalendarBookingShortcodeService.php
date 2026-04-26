<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class EventsCalendarBookingShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class EventsCalendarBookingShortcodeService extends AmeliaBookingShortcodeService
{
    /**
     * @param array $params
     * @return string
     * @throws InvalidArgumentException
     */
    public static function shortcodeHandler($params)
    {
        $params = shortcode_atts(
            [
                'trigger'       => '',
                'trigger_type'  => '',
                'in_dialog'     => '',
                'counter'       => self::$counter,
                'event'         => null,
                'recurring'     => null,
                'employee'      => null,
                'tag'           => null,
                'today'         => null,
                'location'      => null,
            ],
            $params
        );

        if (!empty($params['tag'])) {
            $params['tag'] = htmlspecialchars_decode($params['tag']);
        }

        self::prepareScriptsAndStyles();

        ob_start();
        include AMELIA_PATH . '/view/frontend/events-calendar-booking.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
