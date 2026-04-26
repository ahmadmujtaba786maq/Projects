<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Booking\Event;

use AmeliaBooking\Application\Controller\Booking\Event\AddEventController;
use AmeliaBooking\Application\Controller\Booking\Event\DeleteEventBookingController;
use AmeliaBooking\Application\Controller\Booking\Event\DeleteEventController;
use AmeliaBooking\Application\Controller\Booking\Event\GetEventBookingsController;
use AmeliaBooking\Application\Controller\Booking\Event\GetEventController;
use AmeliaBooking\Application\Controller\Booking\Event\GetEventDeleteEffectController;
use AmeliaBooking\Application\Controller\Booking\Event\GetEventsController;
use AmeliaBooking\Application\Controller\Booking\Event\GetCalendarEventsController;
use AmeliaBooking\Application\Controller\Booking\Event\UpdateEventBookingController;
use AmeliaBooking\Application\Controller\Booking\Event\UpdateEventController;
use AmeliaBooking\Application\Controller\Booking\Event\UpdateEventStatusController;
use Slim\App;

/**
 * Class Event
 *
 * @package AmeliaBooking\Infrastructure\Routes\Booking\Event
 */
class Event
{
    /**
     * @param App $app
     *
     * @throws \InvalidArgumentException
     */
    public static function routes(App $app)
    {
        $app->get('/events', GetEventsController::class);

        $app->get('/events/{id:[0-9]+}', GetEventController::class);

        $app->post('/events', AddEventController::class);

        $app->post('/events/delete/{id:[0-9]+}', DeleteEventController::class);

        $app->post('/events/{id:[0-9]+}', UpdateEventController::class);

        $app->get('/events/effect/{id:[0-9]+}', GetEventDeleteEffectController::class);

        $app->post('/events/bookings/delete/{id:[0-9]+}', DeleteEventBookingController::class);

        $app->post('/events/bookings/{id:[0-9]+}', UpdateEventBookingController::class);

        $app->post('/events/status/{id:[0-9]+}', UpdateEventStatusController::class);

        $app->post('/events/calendar', GetCalendarEventsController::class);

        $app->get('/bookings/events', GetEventBookingsController::class);
    }
}
