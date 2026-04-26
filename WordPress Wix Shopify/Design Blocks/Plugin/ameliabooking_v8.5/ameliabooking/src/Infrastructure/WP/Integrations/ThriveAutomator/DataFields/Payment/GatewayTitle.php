<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment;

use Thrive\Automator\Items\Data_Field;

class GatewayTitle extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/payment_gateway_title';
    }

    public static function get_supported_filters()
    {
        return ['string_ec'];
    }

    public static function get_name()
    {
        return 'Payment Gateway Title';
    }

    public static function get_description()
    {
        return 'Payment Gateway Title';
    }

    public static function get_placeholder()
    {
        return 'payment_gateway_title';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
