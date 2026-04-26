<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class SpecialDayPeriodLocation
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class SpecialDayPeriodLocation
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $locationId;

    /**
     * SpecialDayPeriodLocation constructor.
     *
     * @param Id $locationId
     */
    public function __construct(
        Id $locationId
    ) {
        $this->locationId = $locationId;
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
     * @return array
     */
    public function toArray()
    {
        return [
            'id'         => null !== $this->getId() ? $this->getId()->getValue() : null,
            'locationId' => $this->locationId->getValue(),
        ];
    }
}
