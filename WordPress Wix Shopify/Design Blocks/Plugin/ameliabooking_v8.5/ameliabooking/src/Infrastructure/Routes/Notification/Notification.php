<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Notification;

use AmeliaBooking\Application\Controller\Notification\AddNotificationController;
use AmeliaBooking\Application\Controller\Notification\DeleteNotificationController;
use AmeliaBooking\Application\Controller\Notification\GetNotificationsController;
use AmeliaBooking\Application\Controller\Notification\GetSMSNotificationsHistoryController;
use AmeliaBooking\Application\Controller\Notification\SendAmeliaSmsApiRequestController;
use AmeliaBooking\Application\Controller\Notification\SendScheduledNotificationsController;
use AmeliaBooking\Application\Controller\Notification\SendTestEmailController;
use AmeliaBooking\Application\Controller\Notification\SendUndeliveredNotificationsController;
use AmeliaBooking\Application\Controller\Notification\UpdateNotificationController;
use AmeliaBooking\Application\Controller\Notification\UpdateNotificationStatusController;
use AmeliaBooking\Application\Controller\Notification\UpdateSMSNotificationHistoryController;
use Slim\App;

/**
 * Class Notification
 *
 * @package AmeliaBooking\Infrastructure\Routes\Notification
 */
class Notification
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/notifications', GetNotificationsController::class);

        $app->post('/notifications', AddNotificationController::class);

        $app->post('/notifications/{id:[0-9]+}', UpdateNotificationController::class);

        $app->post('/notifications/status/{id:[0-9]+}', UpdateNotificationStatusController::class);

        $app->post('/notifications/email/test', SendTestEmailController::class);

        $app->get('/notifications/scheduled/send', SendScheduledNotificationsController::class);

        $app->get('/notifications/undelivered/send', SendUndeliveredNotificationsController::class);

        $app->post('/notifications/sms', SendAmeliaSmsApiRequestController::class);

        $app->post('/notifications/sms/history/{id:[0-9]+}', UpdateSMSNotificationHistoryController::class);

        $app->get('/notifications/sms/history', GetSMSNotificationsHistoryController::class);

        $app->post('/notifications/delete/{id:[0-9]+}', DeleteNotificationController::class);
    }
}
