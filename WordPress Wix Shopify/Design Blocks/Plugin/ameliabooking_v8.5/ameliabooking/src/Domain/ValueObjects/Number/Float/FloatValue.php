<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Float;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class FloatValue
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Float
 */
final class FloatValue
{
    /**
     * @var string
     */
    private $float;

    /**
     * Name constructor.
     *
     * @param string $float
     *
     * @throws InvalidArgumentException
     */
    public function __construct($float)
    {
        if (empty($float)) {
            throw new InvalidArgumentException("Float can't be empty");
        }
        if (!filter_var($float, FILTER_VALIDATE_FLOAT)) {
            throw new InvalidArgumentException("Float '$float' must be float");
        }
        $this->float = $float;
    }

    /**
     * Return the float from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->float;
    }
}
