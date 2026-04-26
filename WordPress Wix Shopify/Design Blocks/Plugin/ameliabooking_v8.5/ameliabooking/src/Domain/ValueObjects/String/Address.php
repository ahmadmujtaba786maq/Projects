<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Address
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Address
{
    public const MAX_LENGTH = 255;
    /**
     * @var string
     */
    private $address;

    /**
     * Address constructor.
     *
     * @param string $address
     *
     * @throws InvalidArgumentException
     */
    public function __construct($address)
    {
        if ($address && strlen($address) > static::MAX_LENGTH) {
            throw new InvalidArgumentException("Name '$address' must be less than " . static::MAX_LENGTH . ' chars');
        }
        $this->address = $address;
    }

    /**
     * Return the address from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->address;
    }
}
