<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider;

use Thrive\Automator\Items\Data_Field;

class FirstName extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/employee_first_name';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Employee First Name';
    }

    public static function get_description()
    {
        return 'Employee First Name';
    }

    public static function get_placeholder()
    {
        return 'employee_first_name';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
