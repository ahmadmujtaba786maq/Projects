<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class AppointmentBookingStatusUpdated extends AbstractAppointmentBooking
{
    public static function get_id()
    {
        return 'ameliabooking/appointment-booking-status-updated-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaAppointmentBookingStatusUpdated';
    }

    public static function get_hook_params_number()
    {
        return 2;
    }

    public static function get_name()
    {
        return 'Appointment Booking Status Changed';
    }

    public static function get_description()
    {
        return 'When appointment booking status is changed';
    }
}
