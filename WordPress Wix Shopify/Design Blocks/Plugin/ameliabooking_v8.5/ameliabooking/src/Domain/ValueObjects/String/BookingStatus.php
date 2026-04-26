<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class Status
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class BookingStatus
{
    public const CANCELED = 'canceled';
    public const APPROVED = 'approved';
    public const PENDING  = 'pending';
    public const REJECTED = 'rejected';
    public const NO_SHOW  = 'no-show';
    public const WAITING  = 'waiting';

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
