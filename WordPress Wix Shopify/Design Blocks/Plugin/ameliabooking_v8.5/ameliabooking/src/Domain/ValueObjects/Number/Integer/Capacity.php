<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

final class Capacity
{
    public const MIN = 1;
    public const MAX = 10;
    /**
     * @var string
     */
    private $capacity;

    /**
     * Capacity constructor.
     *
     * @param string $capacity
     *
     * @throws InvalidArgumentException
     */
    public function __construct($capacity)
    {
        if (!filter_var($capacity, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException(
                "Capacity '$capacity' must be whole number between " .
                self::MIN . ' and ' . self::MAX
            );
        }
        $this->capacity = (int)$capacity;
    }

    /**
     * Return the capacity from the value object
     *
     * @return int
     */
    public function getValue()
    {
        return $this->capacity;
    }
}
