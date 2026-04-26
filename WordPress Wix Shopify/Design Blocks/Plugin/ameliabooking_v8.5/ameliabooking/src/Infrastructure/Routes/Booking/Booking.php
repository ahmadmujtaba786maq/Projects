<?php

/**
 * @copyright Â© TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Booking;

use AmeliaBooking\Application\Controller\Booking\Appointment\CancelBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\CancelBookingRemotelyController;
use AmeliaBooking\Application\Controller\Booking\Appointment\ApproveBookingRemotelyController;
use AmeliaBooking\Application\Controller\Booking\Appointment\DeleteBookingRemotelyController;
use AmeliaBooking\Application\Controller\Booking\Appointment\RejectBookingRemotelyController;
use AmeliaBooking\Application\Controller\Booking\Appointment\DeleteBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetIcsController;
use AmeliaBooking\Application\Controller\Booking\Appointment\ReassignBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\SuccessfulBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\AddBookingController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateBookingStatusController;
use Slim\App;

/**
 * Class Booking
 *
 * @package AmeliaBooking\Infrastructure\Routes\Booking
 */
class Booking
{
    /**
     * @param App $app
     *
     * @throws \InvalidArgumentException
     */
    public static function routes(App $app)
    {
        $app->post('/bookings/cancel/{id:[0-9]+}', CancelBookingController::class);

        $app->get('/bookings/cancel/{id:[0-9]+}', CancelBookingRemotelyController::class);

        $app->post('/bookings/delete/{id:[0-9]+}', DeleteBookingController::class);

        $app->post('/bookings/delete/remotely/{id:[0-9]+}', DeleteBookingRemotelyController::class);

        $app->post('/bookings/reassign/{id:[0-9]+}', ReassignBookingController::class);

        $app->post('/bookings/status/{id:[0-9]+}', UpdateBookingStatusController::class);

        $app->post('/bookings', AddBookingController::class);

        $app->get('/bookings/ics/{id:[0-9]+}', GetIcsController::class)->setOutputBuffering(false);

        $app->post('/bookings/success/{id:[0-9]+}', SuccessfulBookingController::class);

        $app->get('/bookings/success/{id:[0-9]+}', ApproveBookingRemotelyController::class);

        $app->get('/bookings/reject/{id:[0-9]+}', RejectBookingRemotelyController::class);
    }
}
