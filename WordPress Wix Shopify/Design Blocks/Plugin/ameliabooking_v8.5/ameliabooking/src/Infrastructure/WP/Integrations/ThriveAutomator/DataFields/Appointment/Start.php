<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Appointment;

use Thrive\Automator\Items\Data_Field;

class Start extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/appointment_start';
    }

    public static function get_supported_filters()
    {
        return ['time_date'];
    }

    public static function get_name()
    {
        return 'Appointment Start';
    }

    public static function get_description()
    {
        return 'Appointment Start';
    }

    public static function get_placeholder()
    {
        return 'appointment_start';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
