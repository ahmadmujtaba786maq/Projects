<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment;

use Thrive\Automator\Items\Data_Field;

class WcOrderId extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/payment_wc_order_id';
    }

    public static function get_supported_filters()
    {
        return ['number_comparison'];
    }

    public static function get_name()
    {
        return 'Woo Commerce Order Id';
    }

    public static function get_description()
    {
        return 'Woo Commerce Order Id';
    }

    public static function get_placeholder()
    {
        return 'payment_wc_order_id';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
