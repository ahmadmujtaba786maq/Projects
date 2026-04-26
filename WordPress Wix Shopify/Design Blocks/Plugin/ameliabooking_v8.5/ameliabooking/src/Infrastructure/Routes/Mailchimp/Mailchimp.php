<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Mailchimp;

use AmeliaBooking\Application\Controller\Mailchimp\DisconnectFromMailchimpController;
use AmeliaBooking\Application\Controller\Mailchimp\FetchAccessTokenMailchimpController;
use AmeliaBooking\Application\Controller\Mailchimp\GetMailchimpAuthURLController;
use Slim\App;

/**
 * Class Mailchimp
 *
 * @package AmeliaBooking\Infrastructure\Routes\Mailchimp
 */
class Mailchimp
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/mailchimp/authorization/url', GetMailchimpAuthURLController::class);

        $app->get('/mailchimp/authorization/token', FetchAccessTokenMailchimpController::class);

        $app->post('/mailchimp/disconnect', DisconnectFromMailchimpController::class);
    }
}
