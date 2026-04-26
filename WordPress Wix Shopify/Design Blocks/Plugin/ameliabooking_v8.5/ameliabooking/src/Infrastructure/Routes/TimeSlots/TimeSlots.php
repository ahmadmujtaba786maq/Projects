<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\TimeSlots;

use AmeliaBooking\Application\Controller\Booking\Appointment\GetTimeSlotsController;
use Slim\App;

/**
 * Class TimeSlots
 *
 * @package AmeliaBooking\Infrastructure\Routes\TimeSlots
 */
class TimeSlots
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/slots', GetTimeSlotsController::class);
    }
}
