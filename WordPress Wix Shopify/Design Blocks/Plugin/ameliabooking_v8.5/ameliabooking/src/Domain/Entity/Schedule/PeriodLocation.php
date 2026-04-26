<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Schedule;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class PeriodLocation
 *
 * @package AmeliaBooking\Domain\Entity\Schedule
 */
class PeriodLocation
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $locationId;

    /**
     * PeriodLocation constructor.
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

    public function toArray()
    {
        return [
            'id'         => null !== $this->getId() ? $this->getId()->getValue() : null,
            'locationId' => $this->locationId->getValue(),
        ];
    }
}
