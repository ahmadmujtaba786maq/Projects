<?php

namespace AmeliaBooking\Application\Services\Deposit;

use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;

/**
 * Class StarterDepositApplicationService
 *
 * @package AmeliaBooking\Application\Services\Deposit
 */
class StarterDepositApplicationService extends AbstractDepositApplicationService
{
    /**
     * @param float            $paymentAmount
     * @param AbstractBookable $bookable
     * @param int              $persons
     *
     * @return float
     */
    public function calculateDepositAmount($paymentAmount, $bookable, $persons)
    {
        return $paymentAmount;
    }
}
