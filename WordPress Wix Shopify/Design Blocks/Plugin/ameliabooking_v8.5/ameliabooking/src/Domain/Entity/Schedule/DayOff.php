<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\DateRepeat;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class DayOff
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class DayOff
{
    /** @var Id */
    private $id;

    /** @var Name */
    private $name;

    /** @var DateTimeValue */
    private $startDate;

    /** @var DateTimeValue */
    private $endDate;

    /** @var DateRepeat */
    private $repeat;

    /**
     * DayOff constructor.
     *
     * @param Name          $name
     * @param DateTimeValue $startDate
     * @param DateTimeValue $endDate
     * @param DateRepeat    $repeat
     */
    public function __construct(
        Name $name,
        DateTimeValue $startDate,
        DateTimeValue $endDate,
        DateRepeat $repeat
    ) {
        $this->name      = $name;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->repeat    = $repeat;
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
     * @return DateRepeat
     */
    public function getRepeat()
    {
        return $this->repeat;
    }

    /**
     * @param DateRepeat $repeat
     */
    public function setRepeat(DateRepeat $repeat)
    {
        $this->repeat = $repeat;
    }

    /**
     * @return Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Name $name
     */
    public function setName(Name $name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'        => null !== $this->id ? $this->id->getValue() : null,
            'name'      => $this->name->getValue(),
            'startDate' => $this->startDate->getValue()->format('Y-m-d'),
            'endDate'   => $this->endDate->getValue()->format('Y-m-d'),
            'repeat'    => $this->repeat->getValue(),
        ];
    }
}
