<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Name
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Name
{
    public const MAX_LENGTH = 255;
    /**
     * @var string
     */
    private $name;

    /**
     * Name constructor.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     */
    public function __construct($name)
    {
        if (empty($name)) {
            $name = '';
        }

        if (strlen($name) > static::MAX_LENGTH) {
            throw new InvalidArgumentException("Name '$name' must be less than " . static::MAX_LENGTH . ' chars');
        }

        $this->name = $name;
    }

    /**
     * Return the name from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->name;
    }
}
