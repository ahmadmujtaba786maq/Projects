<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class Persons extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_persons';
    }

    public static function get_supported_filters()
    {
        return ['number_comparison'];
    }

    public static function get_name()
    {
        return 'Booking Persons';
    }

    public static function get_description()
    {
        return 'Booking Persons';
    }

    public static function get_placeholder()
    {
        return 'booking_persons';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
