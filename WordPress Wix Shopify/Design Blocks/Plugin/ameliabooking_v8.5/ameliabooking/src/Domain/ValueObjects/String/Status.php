<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class Status
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Status
{
    public const HIDDEN   = 'hidden';
    public const VISIBLE  = 'visible';
    public const DISABLED = 'disabled';
    public const BLOCKED  = 'blocked';
    /**
     * @var string
     */
    private $status;

    /**
     * Status constructor.
     *
     * @param string $status
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
