<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\DateTime;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class TimeOfDay
 *
 * @package AmeliaBooking\Domain\ValueObjects\DateTime
 */
final class TimeOfDay
{
    /** @var bool */
    private $value;

    /**
     * TimeOfDay constructor.
     *
     * @param $value
     *
     * @throws InvalidArgumentException
     */
    public function __construct($value)
    {
        $time   = strtotime($value);
        $hour   = (int)date('H', $time);
        $minute = (int)date('i', $time);
        $second = (int)date('s', $time);

        if ($hour < 0 || $hour > 24) {
            throw new InvalidArgumentException(sprintf('%s should be in range %d-%d', '$hour', 0, 24));
        }

        if ($minute < 0 || $minute > 59) {
            throw new InvalidArgumentException(sprintf('%s should be in range %d-%d', '$minute', 0, 59));
        }

        if ($second < 0 || $second > 59) {
            throw new InvalidArgumentException(sprintf('%s should be in range %d-%d', '$second', 0, 59));
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
