<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class Extras extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/extras';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Extras';
    }

    public static function get_description()
    {
        return 'Extras';
    }

    public static function get_placeholder()
    {
        return 'extras';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_ARRAY;
    }
}
