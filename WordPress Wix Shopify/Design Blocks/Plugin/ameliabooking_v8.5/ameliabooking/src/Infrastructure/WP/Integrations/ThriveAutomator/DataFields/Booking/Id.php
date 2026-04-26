<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class Id extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_id';
    }

    public static function get_supported_filters()
    {
        return ['number_comparison'];
    }

    public static function get_name()
    {
        return 'Booking Id';
    }

    public static function get_description()
    {
        return 'Booking Id';
    }

    public static function get_placeholder()
    {
        return 'booking_id';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
