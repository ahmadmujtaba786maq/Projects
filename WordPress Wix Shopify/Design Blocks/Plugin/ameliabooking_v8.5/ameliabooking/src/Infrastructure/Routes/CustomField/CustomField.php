<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\CustomField;

use AmeliaBooking\Application\Controller\CustomField\GetCustomFieldFileController;
use AmeliaBooking\Application\Controller\CustomField\GetCustomFieldsController;
use AmeliaBooking\Application\Controller\CustomField\AddCustomFieldController;
use AmeliaBooking\Application\Controller\CustomField\DeleteCustomFieldController;
use AmeliaBooking\Application\Controller\CustomField\UpdateCustomFieldController;
use AmeliaBooking\Application\Controller\CustomField\UpdateCustomFieldsPositionsController;
use Slim\App;

/**
 * Class Category
 *
 * @package AmeliaBooking\Infrastructure\Routes\CustomField
 */
class CustomField
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->get('/fields', GetCustomFieldsController::class);

        $app->get('/fields/{id:[0-9]+}/{bookingId:[0-9]+}/{index:[0-9]+}', GetCustomFieldFileController::class);

        $app->post('/fields', AddCustomFieldController::class);

        $app->post('/fields/delete/{id:[0-9]+}', DeleteCustomFieldController::class);

        $app->post('/fields/{id:[0-9]+}', UpdateCustomFieldController::class);

        $app->post('/fields/positions', UpdateCustomFieldsPositionsController::class);
    }
}
