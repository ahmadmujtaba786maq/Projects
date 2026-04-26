<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Apps;

use Thrive\Automator\Items\App;

class AmeliaBooking extends App
{
    public static function get_id()
    {
        return 'ameliabooking/amelia_booking_app';
    }

    public static function get_name()
    {
        return 'Amelia Booking';
    }

    public static function get_description()
    {
        return 'This is Amelia Booking';
    }

    public static function get_logo()
    {
        return AMELIA_URL . 'public/img/amelia-logo-symbol.svg';
    }

    public static function has_access()
    {
        return true;
    }
}
