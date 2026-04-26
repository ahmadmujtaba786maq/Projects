<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Bookable\Package;

use AmeliaBooking\Application\Commands\Bookable\Package\AddPackageCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class AddPackageController
 *
 * @package AmeliaBooking\Application\Controller\Bookable\Package
 */
class AddPackageController extends Controller
{
    /**
     * Fields for package that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'color',
        'description',
        'gallery',
        'name',
        'pictureFullPath',
        'pictureThumbPath',
        'price',
        'position',
        'calculatedPrice',
        'discount',
        'bookable',
        'status',
        'settings',
        'endDate',
        'durationCount',
        'durationType',
        'translations',
        'deposit',
        'depositPayment',
        'fullPayment',
        'sharedCapacity',
        'quantity',
        'limitPerCustomer',
    ];

    /**
     * Instantiates the Add Package command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddPackageCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddPackageCommand($args);

        $requestBody = $request->getParsedBody();

        $this->filter($requestBody);
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
