<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\Collection\Collection;

/**
 * Class WeekDay
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class WeekDay
{
    /** @var Id */
    private $id;

    /** @var IntegerValue */
    private $dayIndex;

    /** @var DateTimeValue */
    private $startTime;

    /** @var DateTimeValue */
    private $endTime;

    /** @var Collection */
    private $timeOutList;

    /** @var Collection */
    private $periodList;

    /**
     * WeekDay constructor.
     *
     * @param IntegerValue  $dayIndex
     * @param DateTimeValue $startTime
     * @param DateTimeValue $endTime
     * @param Collection    $timeOutList
     * @param Collection    $periodList
     */
    public function __construct(
        IntegerValue $dayIndex,
        DateTimeValue $startTime,
        DateTimeValue $endTime,
        Collection $timeOutList,
        Collection $periodList
    ) {
        $this->dayIndex    = $dayIndex;
        $this->startTime   = $startTime;
        $this->endTime     = $endTime;
        $this->timeOutList = $timeOutList;
        $this->periodList  = $periodList;
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
     * @return IntegerValue
     */
    public function getDayIndex()
    {
        return $this->dayIndex;
    }

    /**
     * @param IntegerValue $dayIndex
     */
    public function setDayIndex(IntegerValue $dayIndex)
    {
        $this->dayIndex = $dayIndex;
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
     * @return Collection
     */
    public function getTimeOutList()
    {
        return $this->timeOutList;
    }

    /**
     * @param Collection $timeOutList
     */
    public function setTimeOutList(Collection $timeOutList)
    {
        $this->timeOutList = $timeOutList;
    }

    /**
     * @return Collection
     */
    public function getPeriodList()
    {
        return $this->periodList;
    }

    /**
     * @param Collection $periodList
     */
    public function setPeriodList(Collection $periodList)
    {
        $this->periodList = $periodList;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'          => null !== $this->getId() ? $this->getId()->getValue() : null,
            'dayIndex'    => $this->dayIndex->getValue(),
            'startTime'   => $this->startTime->getValue()->format('H:i:s'),
            'endTime'     => $this->endTime->getValue()->format('H:i:s') === '00:00:00' ?
                '24:00:00' : $this->endTime->getValue()->format('H:i:s'),
            'timeOutList' => $this->timeOutList->toArray(),
            'periodList'  => $this->periodList->toArray(),
        ];
    }
}
