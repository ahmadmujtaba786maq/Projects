<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Event;

use Thrive\Automator\Items\Data_Field;

class Name extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/event_name';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Event Name';
    }

    public static function get_description()
    {
        return 'Event Name';
    }

    public static function get_placeholder()
    {
        return 'event_name';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
