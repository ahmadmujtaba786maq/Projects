<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class EventBookingRescheduled extends AbstractEventBooking
{
    public static function get_id()
    {
        return 'ameliabooking/event-booking-rescheduled-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaEventBookingTimeUpdated';
    }

    public static function get_hook_params_number()
    {
        return 2;
    }

    public static function get_name()
    {
        return 'Event Booking Rescheduled';
    }

    public static function get_description()
    {
        return 'When event booking is rescheduled';
    }
}
