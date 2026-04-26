<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Gender
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class Gender
{
    public const GENDER_MALE   = 'male';
    public const GENDER_FEMALE = 'female';

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     *
     * @throws InvalidArgumentException
     */
    public function __construct($value)
    {
        if (!in_array($value, [self::GENDER_MALE, self::GENDER_FEMALE, null], true)) {
            throw new InvalidArgumentException('Not valid gender option');
        }
        $this->value = $value;
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
