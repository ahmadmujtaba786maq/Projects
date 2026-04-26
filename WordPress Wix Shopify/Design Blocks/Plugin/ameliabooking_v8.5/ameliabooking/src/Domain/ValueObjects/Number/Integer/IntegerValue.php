<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class IntegerValue
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Integer
 */
final class IntegerValue
{
    /**
     * @var string
     */
    private $integer;

    /**
     * @param string $integer
     *
     * @throws InvalidArgumentException
     */
    public function __construct($integer)
    {
        if (filter_var($integer, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Number '$integer' must be whole number");
        }

        $this->integer = (int)$integer;
    }

    /**
     * Return the number from the value object
     *
     * @return int
     */
    public function getValue()
    {
        return $this->integer;
    }
}
