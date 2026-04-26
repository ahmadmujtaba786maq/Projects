<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class WholeNumber
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Integer
 */
final class WholeNumber
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
        if (filter_var($integer, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            throw new InvalidArgumentException("Number '$integer' must be integer");
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
