<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Color
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Color
{
    public const MAX_LENGTH = 255;
    /**
     * @var string
     */
    private $color;

    /**
     * Name constructor.
     *
     * @param string $color
     *
     * @throws InvalidArgumentException
     */
    public function __construct($color)
    {
        if (empty($color)) {
            throw new InvalidArgumentException("Color can't be empty");
        }

        if (strlen($color) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Color \"{$color}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        $this->color = $color;
    }

    /**
     * Return the color from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->color;
    }
}
