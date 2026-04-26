<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects;

/**
 * Class Discount
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class Discount
{
    public const PERCENTAGE = 1;
    public const FIXED      = 2;

    /** @var int|null */
    private $id;

    /** @var float */
    private $amount;

    /** @var int */
    private $type;

    /**
     * Discount constructor.
     *
     * @param float $amount
     * @param int   $type
     */
    public function __construct($amount, $type = self::PERCENTAGE)
    {
        $this->amount = $amount;
        $this->type   = (int)$type;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }


    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'amount' => $this->getAmount(),
            'type'   => $this->getType(),
        ];
    }
}
