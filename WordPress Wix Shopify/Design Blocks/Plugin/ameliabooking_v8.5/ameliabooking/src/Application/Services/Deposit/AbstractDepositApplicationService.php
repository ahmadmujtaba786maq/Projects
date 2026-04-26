<?php

namespace AmeliaBooking\Application\Services\Deposit;

use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class AbstractDepositApplicationService
 *
 * @package AmeliaBooking\Application\Services\Deposit
 */
abstract class AbstractDepositApplicationService
{
    protected $container;

    /**
     * AbstractDepositApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param float            $paymentAmount
     * @param AbstractBookable $bookable
     * @param int              $persons
     *
     * @return float
     */
    abstract public function calculateDepositAmount($paymentAmount, $bookable, $persons);
}
