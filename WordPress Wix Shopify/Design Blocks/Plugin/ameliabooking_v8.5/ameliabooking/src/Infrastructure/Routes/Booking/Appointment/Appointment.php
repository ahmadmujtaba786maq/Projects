<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Booking\Appointment;

use AmeliaBooking\Application\Controller\Booking\Appointment\AddAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\DeleteAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetAppointmentsController;
use AmeliaBooking\Application\Controller\Booking\Appointment\GetPackageAppointmentsController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateAppointmentController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateAppointmentStatusController;
use AmeliaBooking\Application\Controller\Booking\Appointment\UpdateAppointmentTimeController;
use Slim\App;

/**
 * Class Appointment
 *
 * @package AmeliaBooking\Infrastructure\Routes\Booking\Appointment
 */
class Appointment
{
    /**
     * @param App $app
     *
     * @throws \InvalidArgumentException
     */
    public static function routes(App $app)
    {
        $app->get('/appointments', GetAppointmentsController::class);

        $app->get('/appointments/{id:[0-9]+}', GetAppointmentController::class);

        $app->post('/appointments', AddAppointmentController::class);

        $app->post('/appointments/delete/{id:[0-9]+}', DeleteAppointmentController::class);

        $app->post('/appointments/{id:[0-9]+}', UpdateAppointmentController::class);

        $app->post('/appointments/status/{id:[0-9]+}', UpdateAppointmentStatusController::class);

        $app->post('/appointments/time/{id:[0-9]+}', UpdateAppointmentTimeController::class);

        $app->get('/package/appointments', GetPackageAppointmentsController::class);
    }
}
