<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Phone
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Phone
{
    public const MAX_LENGTH = 63;
    /**
     * @var string
     */
    private $phone;

    /**
     * Phone constructor.
     *
     * @param string $phone
     *
     * @throws InvalidArgumentException
     */
    public function __construct($phone)
    {
        if ($phone && strlen($phone) > static::MAX_LENGTH) {
            throw new InvalidArgumentException("Phone '$phone' must be less than " . static::MAX_LENGTH . ' chars');
        }
        $this->phone = $phone;
    }

    /**
     * Return the phone from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->phone;
    }
}
