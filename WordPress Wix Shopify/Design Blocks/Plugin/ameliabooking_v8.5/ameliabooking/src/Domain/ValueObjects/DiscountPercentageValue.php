<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class DiscountPercentageValue
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class DiscountPercentageValue
{
    /**
     * @var string
     */
    private $value;

    /**
     * DiscountPercentageValue constructor.
     *
     * @param string $value
     *
     * @throws InvalidArgumentException
     */
    public function __construct($value)
    {
        if ($value === null) {
            throw new InvalidArgumentException('Discount can\'t be empty');
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new InvalidArgumentException("Discount \"{$value}\" must be float");
        }

        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('Discount must be between 0 and 100');
        }

        $this->value = (float)$value;
    }

    /**
     * Return the value from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
