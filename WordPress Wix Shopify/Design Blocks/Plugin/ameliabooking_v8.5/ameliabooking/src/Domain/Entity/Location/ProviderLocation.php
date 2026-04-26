<?php

namespace AmeliaBooking\Domain\Entity\Location;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class ProviderLocation
 *
 * @package AmeliaBooking\Domain\Entity\Location
 */
class ProviderLocation
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $locationId;

    /** @var Id */
    private $userId;

    /**
     * @param Id $userId
     * @param Id $locationId
     */
    public function __construct(
        Id $userId,
        Id $locationId
    ) {
        $this->userId     = $userId;
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
     * @return Id
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param Id $userId
     */
    public function setUserId(Id $userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'         => $this->id !== null ? $this->id->getValue() : null,
            'locationId' => $this->locationId->getValue(),
            'userId'     => $this->userId->getValue(),
        ];
    }
}
