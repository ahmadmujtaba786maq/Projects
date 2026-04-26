<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class CancelUrl extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_cancel_url';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Booking Cancel Url';
    }

    public static function get_description()
    {
        return 'Booking Cancel Url';
    }

    public static function get_placeholder()
    {
        return 'booking_cancel_url';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
