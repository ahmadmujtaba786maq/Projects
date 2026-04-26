<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class AppointmentBookingRescheduled extends AbstractAppointmentBooking
{
    public static function get_id()
    {
        return 'ameliabooking/appointment-booking-rescheduled-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaAppointmentBookingTimeUpdated';
    }

    public static function get_hook_params_number()
    {
        return 1;
    }

    public static function get_name()
    {
        return 'Appointment Booking Rescheduled';
    }

    public static function get_description()
    {
        return 'When appointment booking is rescheduled';
    }
}
