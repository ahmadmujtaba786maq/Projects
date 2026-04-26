<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class AmountType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class AmountType
{
    public const FIXED = 'fixed';

    public const PERCENTAGE = 'percentage';
    /**
     * @var string
     */
    private $value;

    /**
     * Status constructor.
     *
     * @param int $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Return the status from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
