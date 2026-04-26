<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Appointment;

use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;

/**
 * Class CustomerBookingExtra
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Appointment
 */
class CustomerBookingExtra
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $customerBookingId;

    /** @var Id */
    private $extraId;

    /** @var PositiveInteger */
    private $quantity;

    /** @var Price */
    protected $price;

    /** @var  BooleanValueObject */
    protected $aggregatedPrice;

    /** @var Json */
    protected $tax;

    /**
     * CustomerBookingExtra constructor.
     *
     * @param Id              $extraId
     */
    public function __construct(
        Id $extraId
    ) {
        $this->extraId = $extraId;
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
    public function getExtraId()
    {
        return $this->extraId;
    }

    /**
     * @param Id $extraId
     */
    public function setExtraId(Id $extraId)
    {
        $this->extraId = $extraId;
    }

    /**
     * @return PositiveInteger
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param PositiveInteger $quantity
     */
    public function setQuantity(PositiveInteger $quantity)
    {
        $this->quantity = $quantity;
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
     * @return BooleanValueObject
     */
    public function getAggregatedPrice()
    {
        return $this->aggregatedPrice;
    }

    /**
     * @param BooleanValueObject $aggregatedPrice
     */
    public function setAggregatedPrice(BooleanValueObject $aggregatedPrice)
    {
        $this->aggregatedPrice = $aggregatedPrice;
    }

    /**
     * @return Json
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param Json $tax
     */
    public function setTax(Json $tax)
    {
        $this->tax = $tax;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                => null !== $this->getId() ? $this->getId()->getValue() : null,
            'customerBookingId' => $this->getCustomerBookingId() ? $this->getCustomerBookingId()->getValue() : null,
            'extraId'           => $this->getExtraId()->getValue(),
            'quantity'          => $this->getQuantity() ? $this->getQuantity()->getValue() : 1,
            'price'             => null !== $this->getPrice() ? $this->getPrice()->getValue() : null,
            'aggregatedPrice'   => $this->getAggregatedPrice() ? $this->getAggregatedPrice()->getValue() : null,
            'tax'               => null !== $this->getTax()
                ? json_decode($this->getTax()->getValue(), true)
                : null,
        ];
    }
}
