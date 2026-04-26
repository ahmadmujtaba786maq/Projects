<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Event;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class EventTag
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Event
 */
class EventTag
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $eventId;

    /** @var  Name */
    protected $name;

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
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param Id $eventId
     */
    public function setEventId(Id $eventId)
    {
        $this->eventId = $eventId;
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
            'id'      => $this->getId() ? $this->getId()->getValue() : null,
            'eventId' => $this->getEventId() ? $this->getEventId()->getValue() : null,
            'name'    => $this->getName() ? $this->getName()->getValue() : null,
        ];
    }
}
