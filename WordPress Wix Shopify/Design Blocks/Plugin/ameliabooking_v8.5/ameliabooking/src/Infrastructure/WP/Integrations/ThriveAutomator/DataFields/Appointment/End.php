<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment;

use Thrive\Automator\Items\Data_Field;

class End extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/appointment_end';
    }

    public static function get_supported_filters()
    {
        return ['time_date'];
    }

    public static function get_name()
    {
        return 'Appointment End';
    }

    public static function get_description()
    {
        return 'Appointment End';
    }

    public static function get_placeholder()
    {
        return 'appointment_end';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
