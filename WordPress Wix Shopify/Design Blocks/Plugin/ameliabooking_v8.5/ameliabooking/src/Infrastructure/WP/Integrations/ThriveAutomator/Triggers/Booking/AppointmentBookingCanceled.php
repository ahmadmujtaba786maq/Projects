<?php

namespace AmeliaBooking\Infrastructure\WP\Integrations\ThriveAutomator\Triggers\Booking;

class AppointmentBookingCanceled extends AbstractAppointmentBooking
{
    public static function get_id()
    {
        return 'ameliabooking/appointment-booking-canceled-trigger';
    }

    public static function get_wp_hook()
    {
        return 'AmeliaAppointmentBookingCanceled';
    }

    public static function get_hook_params_number()
    {
        return 1;
    }

    public static function get_name()
    {
        return 'Appointment Booking Canceled';
    }

    public static function get_description()
    {
        return 'When appointment booking is canceled';
    }
}
