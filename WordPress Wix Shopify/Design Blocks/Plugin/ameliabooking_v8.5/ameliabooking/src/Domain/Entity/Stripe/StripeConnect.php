<?php

namespace AmeliaBooking\Domain\Entity\Stripe;

use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class StripeConnect
 *
 * @package AmeliaBooking\Domain\Entity\Stripe
 */
class StripeConnect
{
    /** @var Name */
    private $id;

    /** @var Price */
    private $amount;

    /**
     * @return Name
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Name $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Price
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param Price $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'          => $this->getId() ? $this->getId()->getValue() : null,
            'amount'      => $this->getAmount() ? $this->getAmount()->getValue() : null,
        ];
    }
}
