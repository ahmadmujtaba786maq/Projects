<?php

namespace AmeliaBooking\Application\Controller\Coupon;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Coupon\UpdateCouponStatusCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateCouponStatusController
 *
 * @package AmeliaBooking\Application\Controller\Coupon
 */
class UpdateCouponStatusController extends Controller
{
    /**
     * Fields for coupon that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'status',
    ];

    /**
     * Instantiates the Update Coupon Status command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateCouponStatusCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateCouponStatusCommand($args);
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
        $eventBus->emit('coupon.updated', $result);
    }
}
