<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;

/**
 * Class PositiveDuration
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class PositiveDuration
{
    /**
     * @var PositiveInteger
     */
    private $positiveDuration;


    /**
     * PositiveDuration constructor.
     *
     * @param $positiveDuration
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $positiveDuration
    ) {
        $this->positiveDuration = new PositiveInteger($positiveDuration);
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->positiveDuration->getValue();
    }
}
