<?php

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Entity\Bookable\AbstractExtra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;

/**
 * Class Extra
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Extra extends AbstractExtra
{
    /** @var Duration */
    private $duration;

    /** @var Id */
    private $serviceId;

    /** @var  BooleanValueObject */
    protected $aggregatedPrice;

    /** @var  Json */
    private $translations;

    /**
     * @return Duration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param Duration $duration
     */
    public function setDuration(Duration $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return Id
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param Id $serviceId
     */
    public function setServiceId(Id $serviceId)
    {
        $this->serviceId = $serviceId;
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
     * @return BookableType
     */
    public function getType()
    {
        return new BookableType(Entities::EXTRA);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'duration'        => $this->getDuration() ? $this->getDuration()->getValue() : null,
                'serviceId'       => $this->getServiceId() ? $this->getServiceId()->getValue() : null,
                'aggregatedPrice' => $this->getAggregatedPrice() ? $this->getAggregatedPrice()->getValue() : null,
                'translations'    => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
            ]
        );
    }
}
