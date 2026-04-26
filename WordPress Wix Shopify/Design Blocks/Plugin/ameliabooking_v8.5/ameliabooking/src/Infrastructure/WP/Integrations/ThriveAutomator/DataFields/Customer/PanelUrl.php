<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Customer;

use Thrive\Automator\Items\Data_Field;

class PanelUrl extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/customer_panel_url';
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        return 'Customer Panel Url';
    }

    public static function get_description()
    {
        return 'Customer Panel Url';
    }

    public static function get_placeholder()
    {
        return 'customer_panel_url';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
