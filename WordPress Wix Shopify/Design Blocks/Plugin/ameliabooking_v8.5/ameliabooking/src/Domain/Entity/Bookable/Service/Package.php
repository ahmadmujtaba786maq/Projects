<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\DiscountPercentageValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;

/**
 * Class Package
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Package extends AbstractBookable
{
    /** @var Collection */
    private $bookable;

    /** @var Collection */
    private $gallery;

    /** @var  Status */
    protected $status;

    /** @var  BooleanValueObject */
    private $calculatedPrice;

    /** @var DiscountPercentageValue */
    private $discount;

    /** @var DateTimeValue */
    private $endDate;

    /** @var PositiveInteger */
    private $durationCount;

    /** @var Label */
    private $durationType;

    /** @var Json */
    private $translations;

    /** @var  BooleanValueObject */
    private $sharedCapacity;

    /** @var  PositiveInteger */
    protected $quantity;

    /** @var  Json */
    protected $limitPerCustomer;


    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @return Collection
     */
    public function getBookable()
    {
        return $this->bookable;
    }

    /**
     * @param Collection $bookable
     */
    public function setBookable(Collection $bookable)
    {
        $this->bookable = $bookable;
    }

    /**
     * @return Collection
     */
    public function getGallery()
    {
        return $this->gallery;
    }

    /**
     * @param Collection $gallery
     */
    public function setGallery(Collection $gallery)
    {
        $this->gallery = $gallery;
    }

    /**
     * @return BooleanValueObject
     */
    public function getCalculatedPrice()
    {
        return $this->calculatedPrice;
    }

    /**
     * @param BooleanValueObject $calculatedPrice
     */
    public function setCalculatedPrice(BooleanValueObject $calculatedPrice)
    {
        $this->calculatedPrice = $calculatedPrice;
    }

    /**
     * @return DiscountPercentageValue
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param DiscountPercentageValue $discount
     */
    public function setDiscount(DiscountPercentageValue $discount)
    {
        $this->discount = $discount;
    }

    /**
     * @return PositiveInteger
     */
    public function getDurationCount()
    {
        return $this->durationCount;
    }

    /**
     * @param PositiveInteger $durationCount
     */
    public function setDurationCount(PositiveInteger $durationCount)
    {
        $this->durationCount = $durationCount;
    }

    /**
     * @return Label
     */
    public function getDurationType()
    {
        return $this->durationType;
    }

    /**
     * @param Label $durationType
     */
    public function setDurationType(Label $durationType)
    {
        $this->durationType = $durationType;
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
     * @return BookableType
     */
    public function getType()
    {
        return new BookableType(Entities::PACKAGE);
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
     * @return BooleanValueObject
     */
    public function getSharedCapacity()
    {
        return $this->sharedCapacity;
    }

    /**
     * @param BooleanValueObject $sharedCapacity
     */
    public function setSharedCapacity(BooleanValueObject $sharedCapacity)
    {
        $this->sharedCapacity = $sharedCapacity;
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
     * @return Json
     */
    public function getLimitPerCustomer()
    {
        return $this->limitPerCustomer;
    }

    /**
     * @param Json $limitPerCustomer
     */
    public function setLimitPerCustomer($limitPerCustomer)
    {
        $this->limitPerCustomer = $limitPerCustomer;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'type'             => $this->getType()->getValue(),
                'status'           => $this->getStatus() ? $this->getStatus()->getValue() : null,
                'gallery'          => $this->getGallery() ? $this->getGallery()->toArray() : [],
                'bookable'         => $this->getBookable() ? $this->getBookable()->toArray() : [],
                'calculatedPrice'  => $this->getCalculatedPrice() ? $this->getCalculatedPrice()->getValue() : null,
                'discount'         => $this->getDiscount() ? $this->getDiscount()->getValue() : null,
                'endDate'          => $this->getEndDate() ?
                    $this->getEndDate()->getValue()->format('Y-m-d') . ' 00:00:00' : null,
                'durationCount'    => $this->getDurationCount() ? $this->getDurationCount()->getValue() : null,
                'durationType'     => $this->getDurationType() ? $this->getDurationType()->getValue() : null,
                'position'         => $this->getPosition() ? $this->getPosition()->getValue() : null,
                'translations'     => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
                'sharedCapacity'   => $this->getSharedCapacity() ? $this->getSharedCapacity()->getValue() : null,
                'quantity'         => $this->getQuantity() ? $this->getQuantity()->getValue() : null,
                'limitPerCustomer' => $this->getLimitPerCustomer() ? $this->getLimitPerCustomer()->getValue() : null,
            ]
        );
    }
}
