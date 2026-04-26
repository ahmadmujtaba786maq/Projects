<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class CatalogBookingShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class CatalogBookingShortcodeService extends AmeliaBookingShortcodeService
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
                'trigger'           => '',
                'trigger_type'      => '',
                'in_dialog'         => '',
                'categories_hidden' => '',
                'show'              => '',
                'package'           => null,
                'category'          => null,
                'service'           => null,
                'employee'          => null,
                'location'          => null,
                'counter'           => self::$counter
            ],
            $params
        );

        self::prepareScriptsAndStyles();

        ob_start();
        include AMELIA_PATH . '/view/frontend/catalog-booking.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
