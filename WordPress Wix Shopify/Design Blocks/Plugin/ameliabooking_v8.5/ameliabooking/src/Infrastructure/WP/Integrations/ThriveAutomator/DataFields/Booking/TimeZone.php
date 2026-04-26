<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class TimeZone extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_time_zone';
    }

    public static function get_supported_filters()
    {
        return ['string_ec'];
    }

    public static function get_name()
    {
        return 'Booking Time Zone';
    }

    public static function get_description()
    {
        return 'Booking Time Zone';
    }

    public static function get_placeholder()
    {
        return 'booking_time_zone';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
