<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment;

use Thrive\Automator\Items\Data_Field;

class Status extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/appointment_status';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Appointment Status';
    }

    public static function get_description()
    {
        return 'Appointment Status';
    }

    public static function get_placeholder()
    {
        return 'appointment_status';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
