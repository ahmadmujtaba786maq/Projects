<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects;

/**
 * Class BooleanValueObject
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class BooleanValueObject
{
    /**
     * @var bool
     */
    private $value;

    /**
     * @param bool $value
     */
    public function __construct($value)
    {
        $this->value = (bool)$value;
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
