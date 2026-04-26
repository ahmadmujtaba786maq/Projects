<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;

/**
 * Class TimeOut
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class TimeOut
{
    /** @var Id */
    private $id;

    /** @var DateTimeValue */
    private $startTime;

    /** @var DateTimeValue */
    private $endTime;

    /**
     * TimeOut constructor.
     *
     * @param DateTimeValue $startTime
     * @param DateTimeValue $endTime
     */
    public function __construct(
        DateTimeValue $startTime,
        DateTimeValue $endTime
    ) {
        $this->startTime = $startTime;
        $this->endTime   = $endTime;
    }

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @return DateTimeValue
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param DateTimeValue $startTime
     */
    public function setStartTime(DateTimeValue $startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * @return DateTimeValue
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param DateTimeValue $endTime
     */
    public function setEndTime(DateTimeValue $endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'        => null !== $this->getId() ? $this->getId()->getValue() : null,
            'startTime' => $this->startTime->getValue()->format('H:i:s'),
            'endTime'   => $this->endTime->getValue()->format('H:i:s'),
        ];
    }
}
