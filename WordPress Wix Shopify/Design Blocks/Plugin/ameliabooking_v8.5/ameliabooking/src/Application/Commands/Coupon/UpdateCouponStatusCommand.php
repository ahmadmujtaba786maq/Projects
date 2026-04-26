<?php

namespace AmeliaBooking\Application\Commands\Coupon;

use AmeliaBooking\Application\Commands\Command;

/**
 * Class UpdateCouponStatusCommand
 *
 * @package AmeliaBooking\Application\Commands\Coupon
 */
class UpdateCouponStatusCommand extends Command
{
    /**
     * UpdateCouponStatusCommand constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        parent::__construct($args);
    }
}
