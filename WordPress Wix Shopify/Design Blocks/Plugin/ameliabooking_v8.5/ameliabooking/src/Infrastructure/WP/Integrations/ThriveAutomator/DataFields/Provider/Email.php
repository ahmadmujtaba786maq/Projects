<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider;

use Thrive\Automator\Items\Data_Field;

class Email extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/employee_email';
    }

    public static function get_supported_filters()
    {
        return ['string_ec'];
    }

    public static function get_name()
    {
        return 'Employee Email';
    }

    public static function get_description()
    {
        return 'Employee Email';
    }

    public static function get_placeholder()
    {
        return 'employee_email';
    }

    public static function get_field_value_type()
    {
        return static::TYPE_STRING;
    }
}
