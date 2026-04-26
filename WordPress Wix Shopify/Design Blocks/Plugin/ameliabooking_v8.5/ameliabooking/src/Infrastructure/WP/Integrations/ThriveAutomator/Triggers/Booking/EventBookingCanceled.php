<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class EventBookingCanceled extends AbstractEventBooking
{
    public static function get_id()
    {
        return 'ameliabooking/event-booking-canceled-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaEventBookingCanceled';
    }

    public static function get_hook_params_number()
    {
        return 1;
    }

    public static function get_name()
    {
        return 'Event Booking Canceled';
    }

    public static function get_description()
    {
        return 'When event booking is canceled';
    }
}
