<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking;

use Thrive\Automator\Items\Data_Field;

class UrlParams extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/booking_url_params';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Booking Url Params';
    }

    public static function get_description()
    {
        return 'Booking Url Params';
    }

    public static function get_placeholder()
    {
        return 'booking_url_params';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
