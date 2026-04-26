<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class FeeType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class FeeType
{
    public const DISABLED = 'disabled';

    public const FIXED = 'fixed';

    public const PERCENTAGE = 'percentage';

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
