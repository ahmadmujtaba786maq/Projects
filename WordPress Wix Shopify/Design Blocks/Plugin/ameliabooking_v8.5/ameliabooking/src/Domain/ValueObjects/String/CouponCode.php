<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class CouponCode
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class CouponCode
{
    public const MAX_LENGTH = 255;
    /**
     * @var string
     */
    private $value;

    /**
     * CouponCode constructor.
     *
     * @param string $value
     *
     * @throws InvalidArgumentException
     */
    public function __construct($value)
    {
        if ($value && strlen($value) > static::MAX_LENGTH) {
            throw new InvalidArgumentException("Code '$value' must be less than " . static::MAX_LENGTH . ' chars');
        }
        $this->value = $value;
    }

    /**
     * Return the code from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
