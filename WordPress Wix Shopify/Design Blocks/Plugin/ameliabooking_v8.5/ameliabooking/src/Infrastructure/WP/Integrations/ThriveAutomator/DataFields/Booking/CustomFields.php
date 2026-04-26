<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class CustomFields extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/custom_fields';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Custom Fields';
    }

    public static function get_description()
    {
        return 'Custom Fields';
    }

    public static function get_placeholder()
    {
        return 'custom_fields';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_ARRAY;
    }
}
