<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class Price extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_price';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Booking Price';
    }

    public static function get_description()
    {
        return 'Booking Price';
    }

    public static function get_placeholder()
    {
        return 'booking_price';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
