<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Routes\Invoice;

use AmeliaBooking\Application\Controller\Invoice\GenerateInvoiceController;
use Slim\App;

/**
 * Class Invoice
 *
 * @package AmeliaBooking\Infrastructure\Routes\Invoice
 */
class Invoice
{
    /**
     * @param App $app
     */
    public static function routes(App $app)
    {
        $app->post('/invoices/{id:[0-9]+}', GenerateInvoiceController::class);
    }
}
