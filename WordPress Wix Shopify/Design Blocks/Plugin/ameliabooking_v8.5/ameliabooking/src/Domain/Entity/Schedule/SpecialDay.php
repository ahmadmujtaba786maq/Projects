<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\Collection\Collection;

/**
 * Class SpecialDay
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class SpecialDay
{
    /** @var Id */
    private $id;

    /** @var DateTimeValue */
    private $startDate;

    /** @var DateTimeValue */
    private $endDate;

    /** @var Collection */
    private $periodList;

    /**
     * SpecialDay constructor.
     *
     * @param DateTimeValue $startDate
     * @param DateTimeValue $endDate
     * @param Collection    $periodList
     */
    public function __construct(
        DateTimeValue $startDate,
        DateTimeValue $endDate,
        Collection $periodList
    ) {
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->periodList = $periodList;
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
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param DateTimeValue $startDate
     */
    public function setStartDate(DateTimeValue $startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return DateTimeValue
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param DateTimeValue $endDate
     */
    public function setEndDate(DateTimeValue $endDate)
    {
        $this->endDate = $endDate;
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
            'id'         => null !== $this->getId() ? $this->getId()->getValue() : null,
            'startDate'  => $this->startDate->getValue()->format('Y-m-d'),
            'endDate'    => $this->endDate->getValue()->format('Y-m-d'),
            'periodList' => $this->periodList->toArray(),
        ];
    }
}
