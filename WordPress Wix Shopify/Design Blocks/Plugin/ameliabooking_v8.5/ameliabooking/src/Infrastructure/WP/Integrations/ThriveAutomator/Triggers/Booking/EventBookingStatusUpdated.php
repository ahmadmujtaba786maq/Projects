<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class EventBookingStatusUpdated extends AbstractEventBooking
{
    public static function get_id()
    {
        return 'ameliabooking/event-booking-status-updated-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaEventBookingStatusUpdated';
    }

    public static function get_hook_params_number()
    {
        return 2;
    }

    public static function get_name()
    {
        return 'Event Booking Status Changed';
    }

    public static function get_description()
    {
        return 'When event booking status is changed';
    }
}
