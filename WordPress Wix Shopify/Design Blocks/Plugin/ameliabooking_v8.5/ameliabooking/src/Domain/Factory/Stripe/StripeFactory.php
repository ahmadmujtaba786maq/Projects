<?php

namespace AmeliaBooking\Domain\Factory\Stripe;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Stripe\StripeConnect;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class StripeFactory
 *
 * @package AmeliaBooking\Domain\Factory\Stripe
 */
class StripeFactory
{
    /**
     * @param array $data
     *
     * @return StripeConnect
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $stripeConnect = new StripeConnect();

        if (isset($data['id'])) {
            $stripeConnect->setId(new Name($data['id']));
        }

        if (isset($data['amount'])) {
            $stripeConnect->setAmount(new Price($data['amount']));
        }

        return $stripeConnect;
    }
}
