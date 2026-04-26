<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\DataFields\Booking\Item;

use Thrive\Automator\Items\Data_Field;

class Item extends Data_Field
{
    public static $ameliaItemData = [];

    public static function get_id()
    {
        return 'ameliabooking/' . static::$ameliaItem['type'] . '_' . static::$ameliaItem['id'];
    }

    public static function get_supported_filters()
    {
        return [];
    }

    public static function get_name()
    {
        $label = '';

        switch (static::$ameliaItem['type']) {
            case ('custom_field'):
                $label = 'Custom Field';

                break;
            case ('extra'):
                $label = 'Extra';

                break;

            case ('ticket'):
                $label = 'Ticket';

                break;
        }

        return $label . ' (' . static::$ameliaItemData[static::$ameliaItem['type']][static::$ameliaItem['id']] . ')';
    }

    public static function get_description()
    {
        return static::get_name();
    }

    public static function get_placeholder()
    {
        return 'ameliabooking/' . static::$ameliaItem['type'] . '_' . static::$ameliaItem['id'];
    }

    public static function get_field_value_type()
    {
        return self::TYPE_STRING;
    }
}
