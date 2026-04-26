<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\HelperService;

use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;

/**
 * Class HelperService
 *
 * @package AmeliaBooking\Infrastructure\WP\HelperService
 */
class HelperService
{
    public static $jsVars = [];

    /**
     * Helper method to add PHP vars to JS vars
     *
     * @param $varName
     * @param $phpVar
     */
    public static function exportJSVar($varName, $phpVar)
    {
        self::$jsVars[$varName] = $phpVar;
    }

    /**
     * Helper method to print PHP vars to JS vars
     */
    public static function printJSVars()
    {
        if (!empty(self::$jsVars)) {
            $jsBlock = '<script type="text/javascript">';
            foreach (self::$jsVars as $varName => $jsVar) {
                $jsBlock .= "var {$varName} = " . json_encode($jsVar) . ';';
            }
            $jsBlock .= '</script>';
            echo $jsBlock;
        }
    }

    /**
     * @param int $orderId
     *
     * @return string|null
     */
    public static function getWooCommerceOrderUrl($orderId)
    {
        return get_edit_post_link($orderId, '');
    }

    /**
     * @param int $orderId
     *
     * @return array
     */
    public static function getWooCommerceOrderItemAmountValues($orderId)
    {
        $order = wc_get_order($orderId);

        $prices = [];

        if ($order) {
            foreach ($order->get_items() as $itemId => $orderItem) {
                $data = wc_get_order_item_meta($itemId, WooCommerceService::AMELIA);

                if ($data && is_array($data)) {
                    $prices[$itemId] = [
                        'coupon' => (float)round($orderItem->get_subtotal() - $orderItem->get_total(), 2),
                        'tax'    => !isset($data['taxIncluded']) || !$data['taxIncluded'] ?
                            (float)$orderItem->get_total_tax() : 0,
                    ];
                }
            }
        }

        return $prices;
    }
}
