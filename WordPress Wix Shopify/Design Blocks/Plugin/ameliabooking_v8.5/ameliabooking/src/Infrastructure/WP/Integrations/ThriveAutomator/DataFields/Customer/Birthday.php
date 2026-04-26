<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer;

use Thrive\Automator\Items\Data_Field;

class Birthday extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/customer_birthday';
    }

    public static function get_supported_filters()
    {
        return ['time_date'];
    }

    public static function get_name()
    {
        return 'Customer Birthday';
    }

    public static function get_description()
    {
        return 'Customer Birthday';
    }

    public static function get_placeholder()
    {
        return 'customer_birthday';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
