<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class PaymentStatus
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class PaymentStatus
{
    public const PAID = 'paid';

    public const PENDING = 'pending';

    public const PARTIALLY_PAID = 'partiallyPaid';

    public const REFUNDED = 'refunded';

    /**
     * @var string
     */
    private $status;

    /**
     * Status constructor.
     *
     * @param int $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * Return the status from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->status;
    }
}
