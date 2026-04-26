<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Event;

use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;

/**
 * Class CustomerBookingEventTicket
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Event
 */
class CustomerBookingEventTicket
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $customerBookingId;

    /** @var  Id */
    protected $eventTicketId;

    /** @var  IntegerValue */
    protected $persons;

    /** @var  Price */
    protected $price;

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
    public function setId($id)
    {
        $this->id = $id;
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
     * @return Id
     */
    public function getEventTicketId()
    {
        return $this->eventTicketId;
    }

    /**
     * @param Id $eventTicketId
     */
    public function setEventTicketId(Id $eventTicketId)
    {
        $this->eventTicketId = $eventTicketId;
    }

    /**
     * @return IntegerValue
     */
    public function getPersons()
    {
        return $this->persons;
    }

    /**
     * @param IntegerValue $persons
     */
    public function setPersons(IntegerValue $persons)
    {
        $this->persons = $persons;
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                => $this->getId() ? $this->getId()->getValue() : null,
            'eventTicketId'     => $this->getEventTicketId() ? $this->getEventTicketId()->getValue() : null,
            'customerBookingId' => $this->getCustomerBookingId() ? $this->getCustomerBookingId()->getValue() : null,
            'persons'           => $this->getPersons() ? $this->getPersons()->getValue() : null,
            'price'             => $this->getPrice() ? $this->getPrice()->getValue() : null
        ];
    }
}
