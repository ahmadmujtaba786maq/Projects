<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\ShortcodeService;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class CatalogShortcodeService
 *
 * @package AmeliaBooking\Infrastructure\WP\ShortcodeService
 */
class CatalogShortcodeService extends AmeliaShortcodeService
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
                'package'  => null,
                'category' => null,
                'service'  => null,
                'employee' => null,
                'location' => null,
                'counter'  => self::$counter
            ],
            $atts
        );

        self::prepareScriptsAndStyles();

        // Single Category View
        if ($atts['category'] !== null) {
            ob_start();
            include AMELIA_PATH . '/view/frontend/category.inc.php';
            $html = ob_get_contents();
            ob_end_clean();

            return $html;
        }

        // Single Service View
        if ($atts['service'] !== null) {
            ob_start();
            include AMELIA_PATH . '/view/frontend/service.inc.php';
            $html = ob_get_contents();
            ob_end_clean();

            return $html;
        }

        // Single Package View
        if ($atts['package'] !== null) {
            ob_start();
            include AMELIA_PATH . '/view/frontend/package.inc.php';
            $html = ob_get_contents();
            ob_end_clean();

            return $html;
        }

        // All Categories View
        ob_start();
        include AMELIA_PATH . '/view/frontend/catalog.inc.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
