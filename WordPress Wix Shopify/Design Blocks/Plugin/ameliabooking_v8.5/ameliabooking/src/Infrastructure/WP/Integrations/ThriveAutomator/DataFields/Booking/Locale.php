<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class Locale extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_locale';
    }

    public static function get_supported_filters()
    {
        return ['string_ec'];
    }

    public static function get_name()
    {
        return 'Booking Locale';
    }

    public static function get_description()
    {
        return 'Booking Locale';
    }

    public static function get_placeholder()
    {
        return 'booking_locale';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
