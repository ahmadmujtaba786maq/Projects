<?php

namespace AmeliaBooking\Application\Controller\PaymentGateway;

use AmeliaBooking\Application\Commands\PaymentGateway\WooCommerceProductsCommand;
use AmeliaBooking\Application\Controller\Controller;
use RuntimeException;
use Slim\Http\Request;

/**
 * Class WooCommerceProductsController
 *
 * @package AmeliaBooking\Application\Controller\PaymentGateway
 */
class WooCommerceProductsController extends Controller
{
    /**
     * Fields for WooCommerce products that can be received from API
     *
     * @var array
     */
    protected $allowedFields = [
        'name',
    ];

    /**
     * Instantiates the WooCommerce Products Callback command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return WooCommerceProductsCommand
     * @throws RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new WooCommerceProductsCommand($args);

        $requestBody = $request->getParsedBody();

        $command->setField('params', (array)$request->getQueryParams());

        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
