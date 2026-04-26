<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

/**
 * Class Status
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Integer
 */
final class Status
{
    public const INVISIBLE = 0;
    public const VISIBLE   = 1;
    /**
     * @var int
     */
    private $status;

    /**
     * Status constructor.
     *
     * @param int $status
     */
    public function __construct($status)
    {
        $this->status = (int)$status;
    }

    /**
     * Return the status from the value object
     *
     * @return int
     */
    public function getValue()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->status === self::VISIBLE;
    }
}
