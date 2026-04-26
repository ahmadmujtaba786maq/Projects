<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Controller\Coupon;

use AmeliaBooking\Application\Commands\Coupon\AddCouponCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddCouponController
 *
 * @package AmeliaBooking\Application\Controller\Coupon
 */
class AddCouponController extends Controller
{
    /**
     * Fields for coupon that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'code',
        'discount',
        'deduction',
        'limit',
        'customerLimit',
        'status',
        'notificationInterval',
        'notificationRecurring',
        'services',
        'events',
        'packages',
        'expirationDate',
        'allServices',
        'allEvents',
        'allPackages'
    ];

    /**
     * Instantiates the Add Coupon command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return mixed
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new AddCouponCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('coupon.added', $result);
    }
}
