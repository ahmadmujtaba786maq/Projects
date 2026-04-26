<?php

namespace AmeliaBooking\Domain\ValueObjects\DateTime;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;

/**
 * Class Birthday
 *
 * @package AmeliaBooking\Domain\ValueObjects\DateTime
 */
final class Birthday
{
    /**
     * @var string
     */
    private $date;

    /**
     * Birthday Date constructor.
     *
     * @param \DateTime $date
     *
     * @throws InvalidArgumentException
     */
    public function __construct(\DateTime $date)
    {
        if (null === $date) {
            throw new InvalidArgumentException("Date can't be empty");
        }
        if (!($date instanceof \DateTime)) {
            throw new InvalidArgumentException('Date must be a instance of DateTime');
        }

        $this->date = $date;
    }

    /**
     * Return the name from the value object
     *
     * @return \DateTime
     */
    public function getValue()
    {
        return $this->date;
    }
}
