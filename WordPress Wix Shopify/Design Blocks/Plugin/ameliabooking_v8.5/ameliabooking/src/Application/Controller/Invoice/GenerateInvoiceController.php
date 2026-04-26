<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Invoice;

use AmeliaBooking\Application\Commands\Invoice\GenerateInvoiceCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GenerateInvoiceController
 *
 * @package AmeliaBooking\Application\Controller\Invoice
 */
class GenerateInvoiceController extends Controller
{
    /**
     * @var array
     */
    protected $allowedFields = [
        'sendEmail'
    ];

    /**
     * Instantiates the Generate Invoice command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GenerateInvoiceCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new GenerateInvoiceCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
