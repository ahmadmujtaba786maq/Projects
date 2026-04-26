<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Provider;

use Thrive\Automator\Items\Data_Field;

class ExternalId extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/employee_external_id';
    }

    public static function get_supported_filters()
    {
        return ['number_comparison'];
    }

    public static function get_name()
    {
        return 'Employee External Id';
    }

    public static function get_description()
    {
        return 'Employee External Id';
    }

    public static function get_placeholder()
    {
        return 'employee_external_id';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
