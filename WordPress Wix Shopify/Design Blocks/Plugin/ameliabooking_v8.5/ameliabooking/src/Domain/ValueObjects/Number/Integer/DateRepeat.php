<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

/**
 * Class DateRepeat
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Integer
 */
final class DateRepeat
{
    public const ON  = 1;
    public const OFF = 0;
    /**
     * @var int
     */
    private $repeat;

    /**
     * repeat constructor.
     *
     * @param int $repeat
     */
    public function __construct($repeat)
    {
        $this->repeat = (int)$repeat;
    }

    /**
     * Return the repeat from the value object
     *
     * @return int
     */
    public function getValue()
    {
        return $this->repeat;
    }

    /**
     * @return bool
     */
    public function isRepeating()
    {
        return $this->repeat === self::ON;
    }
}
