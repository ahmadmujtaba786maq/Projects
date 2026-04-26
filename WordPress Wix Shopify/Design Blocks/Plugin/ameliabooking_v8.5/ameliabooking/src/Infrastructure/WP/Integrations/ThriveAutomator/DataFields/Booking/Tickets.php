<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class Tickets extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/tickets';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Tickets';
    }

    public static function get_description()
    {
        return 'Tickets';
    }

    public static function get_placeholder()
    {
        return 'tickets';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_ARRAY;
    }
}
