<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Event;

use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;

/**
 * Class EventTicket
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Event
 */
class EventTicket
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $eventId;

    /** @var Name */
    protected $name;

    /** @var BooleanValueObject */
    private $enabled;

    /** @var Price */
    protected $price;

    /** @var Price */
    protected $dateRangePrice;

    /** @var IntegerValue */
    private $spots;

    /** @var IntegerValue */
    private $waiting;

    /** @var IntegerValue */
    private $waitingListSpots;

    /** @var Json */
    protected $dateRanges;

    /** @var IntegerValue */
    private $sold;

    /** @var  Json */
    protected $translations;

    /**
     * @return Json
     */
    public function getDateRanges()
    {
        return $this->dateRanges;
    }

    /**
     * @param Json $dateRanges
     */
    public function setDateRanges($dateRanges)
    {
        $this->dateRanges = $dateRanges;
    }

    /**
     * @return IntegerValue
     */
    public function getSpots()
    {
        return $this->spots;
    }

    /**
     * @param IntegerValue $spots
     */
    public function setSpots($spots)
    {
        $this->spots = $spots;
    }

    /**
     * @return IntegerValue
     */
    public function getWaitingListSpots()
    {
        return $this->waitingListSpots;
    }

    /**
     * @param IntegerValue $waitingListSpots
     */
    public function setWaitingListSpots($waitingListSpots)
    {
        $this->waitingListSpots = $waitingListSpots;
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
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return Price
     */
    public function getDateRangePrice()
    {
        return $this->dateRangePrice;
    }

    /**
     * @param Price $dateRangePrice
     */
    public function setDateRangePrice($dateRangePrice)
    {
        $this->dateRangePrice = $dateRangePrice;
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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return BooleanValueObject
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param BooleanValueObject $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
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
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
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
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return IntegerValue
     */
    public function getSold()
    {
        return $this->sold;
    }

    /**
     * @param IntegerValue $sold
     */
    public function setSold($sold)
    {
        $this->sold = $sold;
    }

    /**
     * @return IntegerValue
     */
    public function getWaiting()
    {
        return $this->waiting;
    }

    /**
     * @param IntegerValue $waiting
     */
    public function setWaiting($waiting)
    {
        $this->waiting = $waiting;
    }

    /**
     * @return Json
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param Json $translations
     */
    public function setTranslations(Json $translations)
    {
        $this->translations = $translations;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'               => $this->getId() ? $this->getId()->getValue() : null,
            'eventId'          => $this->getEventId() ? $this->getEventId()->getValue() : null,
            'name'             => $this->getName() ? $this->getName()->getValue() : null,
            'enabled'          => $this->getEnabled() ? $this->getEnabled()->getValue() : null,
            'price'            => $this->getPrice() ? $this->getPrice()->getValue() : null,
            'dateRangePrice'   => $this->getDateRangePrice() ? $this->getDateRangePrice()->getValue() : null,
            'spots'            => $this->getSpots() ? $this->getSpots()->getValue() : null,
            'waitingListSpots' => $this->getWaitingListSpots() ? $this->getWaitingListSpots()->getValue() : null,
            'dateRanges'       => $this->getDateRanges() ? $this->getDateRanges()->getValue() : null,
            'sold'             => $this->getSold() ? $this->getSold()->getValue() : 0,
            'waiting'          => $this->getWaiting() ? $this->getWaiting()->getValue() : 0,
            'translations'     => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
        ];
    }
}
