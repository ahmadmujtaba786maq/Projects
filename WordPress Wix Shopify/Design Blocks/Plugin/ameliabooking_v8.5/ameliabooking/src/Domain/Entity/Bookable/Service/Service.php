<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\Cycle;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Priority;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;

/**
 * Class Service
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Service extends AbstractBookable
{
    /** @var  IntegerValue */
    private $minCapacity;

    /** @var  IntegerValue */
    private $maxCapacity;

    /** @var  PositiveDuration */
    private $duration;

    /** @var  Duration */
    private $timeBefore;

    /** @var  Duration */
    private $timeAfter;

    /** @var  IntegerValue */
    private $minSelectedExtras;

    /** @var BooleanValueObject */
    private $bringingAnyone;

    /** @var BooleanValueObject */
    private $mandatoryExtra;

    /** @var Priority */
    private $priority;

    /** @var Collection */
    private $gallery;

    /** @var  Status */
    protected $status;

    /** @var  Id */
    protected $categoryId;

    /** @var  Category */
    protected $category;

    /** @var  BooleanValueObject */
    protected $show;

    /** @var  BooleanValueObject */
    protected $aggregatedPrice;

    /** @var  Cycle */
    protected $recurringCycle;

    /** @var  Name */
    protected $recurringSub;

    /** @var  WholeNumber */
    protected $recurringPayment;

    /** @var  Json */
    protected $translations;

    /** @var  Json */
    protected $customPricing;

    /** @var  IntegerValue */
    private $maxExtraPeople;


    /** @var  Json */
    protected $limitPerCustomer;

    /**
     * @return Id
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param Id $categoryId
     */
    public function setCategoryId(Id $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;
    }

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
     * @return IntegerValue
     */
    public function getMinCapacity()
    {
        return $this->minCapacity;
    }

    /**
     * @param IntegerValue $minCapacity
     */
    public function setMinCapacity(IntegerValue $minCapacity)
    {
        $this->minCapacity = $minCapacity;
    }

    /**
     * @return IntegerValue
     */
    public function getMaxCapacity()
    {
        return $this->maxCapacity;
    }

    /**
     * @param IntegerValue $maxCapacity
     */
    public function setMaxCapacity(IntegerValue $maxCapacity)
    {
        $this->maxCapacity = $maxCapacity;
    }

    /**
     * @return PositiveDuration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param PositiveDuration $duration
     */
    public function setDuration(PositiveDuration $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return Duration
     */
    public function getTimeBefore()
    {
        return $this->timeBefore;
    }

    /**
     * @param Duration $timeBefore
     */
    public function setTimeBefore(Duration $timeBefore)
    {
        $this->timeBefore = $timeBefore;
    }

    /**
     * @return IntegerValue
     */
    public function getMinSelectedExtras()
    {
        return $this->minSelectedExtras;
    }

    /**
     * @param IntegerValue $minSelectedExtras
     */
    public function setMinSelectedExtras(IntegerValue $minSelectedExtras)
    {
        $this->minSelectedExtras = $minSelectedExtras;
    }

    /**
     * @return Duration
     */
    public function getTimeAfter()
    {
        return $this->timeAfter;
    }

    /**
     * @param Duration $timeAfter
     */
    public function setTimeAfter(Duration $timeAfter)
    {
        $this->timeAfter = $timeAfter;
    }

    /**
     * @return BooleanValueObject
     */
    public function getBringingAnyone()
    {
        return $this->bringingAnyone;
    }

    /**
     * @param BooleanValueObject $bringingAnyone
     */
    public function setBringingAnyone(BooleanValueObject $bringingAnyone)
    {
        $this->bringingAnyone = $bringingAnyone;
    }

    /**
     * @return BooleanValueObject
     */
    public function getShow()
    {
        return $this->show;
    }

    /**
     * @param BooleanValueObject $show
     */
    public function setShow(BooleanValueObject $show)
    {
        $this->show = $show;
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
     * @return BooleanValueObject
     */
    public function getMandatoryExtra()
    {
        return $this->mandatoryExtra;
    }

    /**
     * @param BooleanValueObject $mandatoryExtra
     */
    public function setMandatoryExtra(BooleanValueObject $mandatoryExtra)
    {
        $this->mandatoryExtra = $mandatoryExtra;
    }

    /**
     * @return Priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Priority $priority
     */
    public function setPriority(Priority $priority)
    {
        $this->priority = $priority;
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
     * @return Cycle
     */
    public function getRecurringCycle()
    {
        return $this->recurringCycle;
    }

    /**
     * @param Cycle $recurringCycle
     */
    public function setRecurringCycle(Cycle $recurringCycle)
    {
        $this->recurringCycle = $recurringCycle;
    }

    /**
     * @return Name
     */
    public function getRecurringSub()
    {
        return $this->recurringSub;
    }

    /**
     * @param Name $recurringSub
     */
    public function setRecurringSub(Name $recurringSub)
    {
        $this->recurringSub = $recurringSub;
    }

    /**
     * @return WholeNumber
     */
    public function getRecurringPayment()
    {
        return $this->recurringPayment;
    }

    /**
     * @param WholeNumber $recurringPayment
     */
    public function setRecurringPayment(WholeNumber $recurringPayment)
    {
        $this->recurringPayment = $recurringPayment;
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
     * @return Json
     */
    public function getCustomPricing()
    {
        return $this->customPricing;
    }

    /**
     * @param Json $customPricing
     */
    public function setCustomPricing(Json $customPricing)
    {
        $this->customPricing = $customPricing;
    }

    /**
     * @return IntegerValue
     */
    public function getMaxExtraPeople()
    {
        return $this->maxExtraPeople;
    }

    /**
     * @param IntegerValue $maxExtraPeople
     */
    public function setMaxExtraPeople($maxExtraPeople)
    {
        $this->maxExtraPeople = $maxExtraPeople;
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
     * @return BookableType
     */
    public function getType()
    {
        return new BookableType(Entities::SERVICE);
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'minCapacity'      => $this->getMinCapacity() ? $this->getMinCapacity()->getValue() : null,
                'maxCapacity'      => $this->getMaxCapacity() ? $this->getMaxCapacity()->getValue() : null,
                'duration'         => $this->getDuration() ? $this->getDuration()->getValue() : null,
                'timeBefore'       => $this->getTimeBefore() ? $this->getTimeBefore()->getValue() : null,
                'timeAfter'        => $this->getTimeAfter() ? $this->getTimeAfter()->getValue() : null,
                'bringingAnyone'   => $this->getBringingAnyone() ? $this->getBringingAnyone()->getValue() : null,
                'show'             => $this->getShow() ? $this->getShow()->getValue() : null,
                'aggregatedPrice'  => $this->getAggregatedPrice() ? $this->getAggregatedPrice()->getValue() : null,
                'status'           => $this->getStatus() ? $this->getStatus()->getValue() : null,
                'categoryId'       => $this->getCategoryId() ? $this->getCategoryId()->getValue() : null,
                'category'         => $this->getCategory() ? $this->getCategory()->toArray() : null,
                'priority'         => $this->getPriority() ? $this->getPriority()->getValue() : [],
                'gallery'          => $this->getGallery() ? $this->getGallery()->toArray() : [],
                'recurringCycle'   => $this->getRecurringCycle() ? $this->getRecurringCycle()->getValue() : null,
                'recurringSub'     => $this->getRecurringSub() ? $this->getRecurringSub()->getValue() : null,
                'recurringPayment' => $this->getRecurringPayment() ? $this->getRecurringPayment()->getValue() : null,
                'translations'     => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
                'minSelectedExtras' => $this->getMinSelectedExtras() ? $this->getMinSelectedExtras()->getValue() : null,
                'mandatoryExtra'   => $this->getMandatoryExtra() ? $this->getMandatoryExtra()->getValue() : null,
                'customPricing'    => $this->getCustomPricing() ? $this->getCustomPricing()->getValue() : null,
                'maxExtraPeople'   => $this->getMaxExtraPeople() ? $this->getMaxExtraPeople()->getValue() : null,
                'limitPerCustomer' => $this->getLimitPerCustomer() ? $this->getLimitPerCustomer()->getValue() : null,
            ]
        );
    }
}
