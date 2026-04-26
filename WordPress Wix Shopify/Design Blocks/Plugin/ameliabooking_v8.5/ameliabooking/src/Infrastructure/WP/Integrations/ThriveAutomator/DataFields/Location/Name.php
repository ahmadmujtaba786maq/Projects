<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Location;

use Thrive\Automator\Items\Data_Field;

class Name extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/location_name';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Location Name';
    }

    public static function get_description()
    {
        return 'Location Name';
    }

    public static function get_placeholder()
    {
        return 'location_name';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
