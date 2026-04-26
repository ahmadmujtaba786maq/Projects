<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Notification;

use AmeliaBooking\Application\Controller\Notification\SendTestWhatsAppController;
use AmeliaBooking\Application\Controller\Notification\WhatsAppWebhookController;
use AmeliaBooking\Application\Controller\Notification\WhatsAppWebhookRegisterController;
use Slim\App;

/**
 * Class WhatsApp
 *
 * @package AmeliaBooking\Infrastructure\Routes\Notification
 */
class WhatsApp
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->post('/notifications/whatsapp/test', SendTestWhatsAppController::class);

        $app->get('/notifications/whatsapp/webhook', WhatsAppWebhookRegisterController::class);

        $app->post('/notifications/whatsapp/webhook', WhatsAppWebhookController::class);
    }
}
