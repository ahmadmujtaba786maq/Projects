<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Event;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class CustomerBookingEventPeriod
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Event
 */
class CustomerBookingEventPeriod
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $customerBookingId;

    /** @var  Id */
    protected $eventPeriodId;

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
    public function getEventPeriodId()
    {
        return $this->eventPeriodId;
    }

    /**
     * @param Id $eventPeriodId
     */
    public function setEventPeriodId(Id $eventPeriodId)
    {
        $this->eventPeriodId = $eventPeriodId;
    }

    /**
     * @return Id
     */
    public function getCustomerBookingId()
    {
        return $this->customerBookingId;
    }

    /**
     * @param Id $customerBookingId
     */
    public function setCustomerBookingId(Id $customerBookingId)
    {
        $this->customerBookingId = $customerBookingId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                => $this->getId() ? $this->getId()->getValue() : null,
            'eventPeriodId'     => $this->getEventPeriodId() ? $this->getEventPeriodId()->getValue() : null,
            'customerBookingId' => $this->getCustomerBookingId() ? $this->getCustomerBookingId()->getValue() : null
        ];
    }
}
