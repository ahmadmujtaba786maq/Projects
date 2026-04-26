<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Float;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Price
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Float
 */
final class Price
{
    /**
     * @var string
     */
    private $price;

    /**
     * Price constructor.
     *
     * @param string $price
     *
     * @throws InvalidArgumentException
     */
    public function __construct($price)
    {
        if ($price === null) {
            throw new InvalidArgumentException('Price can\'t be empty');
        }

        if (
            filter_var($price, FILTER_VALIDATE_FLOAT) === false &&
            filter_var(str_replace(',', '.', (string)$price), FILTER_VALIDATE_FLOAT) === false
        ) {
            throw new InvalidArgumentException("Price \"{$price}\" must be float");
        }

        if ($price < 0) {
            throw new InvalidArgumentException('Price must be larger then or equal to 0');
        }

        $this->price = (float)$price;
    }

    /**
     * Return the price from the value object
     *
     * @return float
     */
    public function getValue()
    {
        return $this->price;
    }
}
