<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer;

use Thrive\Automator\Items\Data_Field;

class ExternalId extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/customer_external_id';
    }

    public static function get_supported_filters()
    {
        return ['number_comparison'];
    }

    public static function get_name()
    {
        return 'Customer External Id';
    }

    public static function get_description()
    {
        return 'Customer External Id';
    }

    public static function get_placeholder()
    {
        return 'customer_external_id';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
