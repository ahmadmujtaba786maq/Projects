<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce;

use AmeliaBooking\Domain\Services\Settings\SettingsService;

/**
 * Class StarterWooCommerceService
 *
 * @package AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce
 */
class StarterWooCommerceService
{
    /** @var SettingsService $settingsService */
    public static $settingsService;

    public const AMELIA = 'ameliabooking';

    public static function init($settingsService)
    {
    }

    public static function setContainer($container)
    {
    }

    public static function isEnabled()
    {
        return false;
    }

    public static function getPaymentLink($orderId)
    {
        return [];
    }

    public static function createWcOrder($productId, $appointmentData, $price, $oldOrderId, $customer)
    {
        return null;
    }

    public static function updateItemMetaData($orderId, $reservation)
    {
    }

    public static function getCacheData($orderId)
    {
        return null;
    }

    public static function getIdForExistingOrNewProduct($postId)
    {
        return $postId;
    }

    public static function getInitialProducts()
    {
    }
}
