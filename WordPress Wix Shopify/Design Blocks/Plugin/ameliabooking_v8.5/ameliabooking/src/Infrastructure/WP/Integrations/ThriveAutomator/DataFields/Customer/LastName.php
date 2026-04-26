<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer;

use Thrive\Automator\Items\Data_Field;

class LastName extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/customer_last_name';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Customer Last Name';
    }

    public static function get_description()
    {
        return 'Customer Last Name';
    }

    public static function get_placeholder()
    {
        return 'customer_last_name';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
