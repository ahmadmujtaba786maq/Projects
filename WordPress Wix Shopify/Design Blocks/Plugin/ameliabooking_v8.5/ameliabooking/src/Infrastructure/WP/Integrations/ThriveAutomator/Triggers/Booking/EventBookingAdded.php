<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class EventBookingAdded extends AbstractEventBooking
{
    public static function get_id()
    {
        return 'ameliabooking/event-booking-added-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaEventBookingAdded';
    }

    public static function get_hook_params_number()
    {
        return 2;
    }

    public static function get_name()
    {
        return 'Event Booking Created';
    }

    public static function get_description()
    {
        return 'When event booking is created';
    }
}
