<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Coupon;

use Thrive\Automator\Items\Data_Field;

class Id extends Data_Field
{
    public static function get_id()
    {
        return 'ameliabooking/coupon_id';
    }

    public static function get_supported_filters()
    {
        return ['number_comparison'];
    }

    public static function get_name()
    {
        return 'Coupon Id';
    }

    public static function get_description()
    {
        return 'Coupon Id';
    }

    public static function get_placeholder()
    {
        return 'coupon_id';
    }

    public static function get_field_value_type()
    {
        return self::TYPE_NUMBER;
    }
}
