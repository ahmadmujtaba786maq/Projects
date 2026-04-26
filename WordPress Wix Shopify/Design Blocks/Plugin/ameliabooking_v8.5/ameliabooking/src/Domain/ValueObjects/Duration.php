<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;

/**
 * Class Duration
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class Duration
{
    /**
     * @var WholeNumber
     */
    private $duration;


    /**
     * Duration constructor.
     *
     * @param $duration
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $duration
    ) {
        $this->duration = new WholeNumber($duration);
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->duration->getValue();
    }
}
