<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class Period
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class Period
{
    /** @var Id */
    private $id;

    /** @var DateTimeValue */
    private $startTime;

    /** @var DateTimeValue */
    private $endTime;

    /** @var Id */
    private $locationId;

    /** @var Collection */
    private $periodServiceList;

    /** @var Collection */
    private $periodLocationList;

    /**
     * Period constructor.
     *
     * @param DateTimeValue $startTime
     * @param DateTimeValue $endTime
     * @param Collection    $periodServiceList
     * @param Collection    $periodLocationList
     */
    public function __construct(
        DateTimeValue $startTime,
        DateTimeValue $endTime,
        Collection $periodServiceList,
        Collection $periodLocationList
    ) {
        $this->startTime = $startTime;

        $this->endTime = $endTime;

        $this->periodServiceList = $periodServiceList;

        $this->periodLocationList = $periodLocationList;
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
     * @return Id
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param Id $locationId
     */
    public function setLocationId(Id $locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return Collection
     */
    public function getPeriodServiceList()
    {
        return $this->periodServiceList;
    }

    /**
     * @param Collection $periodServiceList
     */
    public function setPeriodServiceList(Collection $periodServiceList)
    {
        $this->periodServiceList = $periodServiceList;
    }

    /**
     * @return Collection
     */
    public function getPeriodLocationList()
    {
        return $this->periodLocationList;
    }

    /**
     * @param Collection $periodLocationList
     */
    public function setPeriodLocationList(Collection $periodLocationList)
    {
        $this->periodLocationList = $periodLocationList;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                 => null !== $this->getId() ? $this->getId()->getValue() : null,
            'startTime'          => $this->startTime->getValue()->format('H:i:s'),
            'endTime'            => $this->endTime->getValue()->format('H:i:s') === '00:00:00' ?
                '24:00:00' : $this->endTime->getValue()->format('H:i:s'),
            'locationId'         => $this->locationId ? $this->getLocationId()->getValue() : null,
            'periodServiceList'  => $this->periodServiceList->toArray(),
            'periodLocationList' => $this->periodLocationList->toArray(),
        ];
    }
}
