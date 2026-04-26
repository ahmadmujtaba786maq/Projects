<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Payment;

use Thrive\Automator\Items\Data_Field;

class Status extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/payment_status';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Payment Status';
    }

    public static function get_description()
    {
        return 'Payment Status';
    }

    public static function get_placeholder()
    {
        return 'payment_status';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
