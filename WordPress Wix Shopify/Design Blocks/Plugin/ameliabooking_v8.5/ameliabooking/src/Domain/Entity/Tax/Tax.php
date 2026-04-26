<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Tax;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\FloatValue;
use AmeliaBooking\Domain\ValueObjects\String\AmountType;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class Tax
 *
 * @package AmeliaBooking\Domain\Entity\Tax
 */
class Tax
{
    /** @var Id */
    private $id;

    /** @var Name */
    private $name;

    /** @var FloatValue */
    private $amount;

    /** @var AmountType */
    private $type;

    /** @var Status */
    private $status;

    /** @var BooleanValueObject */
    private $excluded;

    /** @var BooleanValueObject */
    private $allServices;

    /** @var BooleanValueObject */
    private $allEvents;

    /** @var BooleanValueObject */
    private $allPackages;

    /** @var BooleanValueObject */
    private $allExtras;

    /** @var Collection */
    private $serviceList;

    /** @var Collection */
    private $eventList;

    /** @var Collection */
    private $packageList;

    /** @var Collection */
    private $extraList;

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
     * @return FloatValue
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param FloatValue $amount
     */
    public function setAmount(FloatValue $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return AmountType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param AmountType $type
     */
    public function setType(AmountType $type)
    {
        $this->type = $type;
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
     * @return BooleanValueObject
     */
    public function getExcluded()
    {
        return $this->excluded;
    }

    /**
     * @param BooleanValueObject $excluded
     */
    public function setExcluded(BooleanValueObject $excluded)
    {
        $this->excluded = $excluded;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllServices()
    {
        return $this->allServices;
    }

    /**
     * @param BooleanValueObject $allServices
     */
    public function setAllServices(BooleanValueObject $allServices)
    {
        $this->allServices = $allServices;
    }

    /**
     * @return Collection
     */
    public function getServiceList()
    {
        return $this->serviceList;
    }

    /**
     * @param Collection $serviceList
     */
    public function setServiceList(Collection $serviceList)
    {
        $this->serviceList = $serviceList;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllEvents()
    {
        return $this->allEvents;
    }

    /**
     * @param BooleanValueObject $allEvents
     */
    public function setAllEvents(BooleanValueObject $allEvents)
    {
        $this->allEvents = $allEvents;
    }

    /**
     * @return Collection
     */
    public function getEventList()
    {
        return $this->eventList;
    }

    /**
     * @param Collection $eventList
     */
    public function setEventList(Collection $eventList)
    {
        $this->eventList = $eventList;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllPackages()
    {
        return $this->allPackages;
    }

    /**
     * @param BooleanValueObject $allPackages
     */
    public function setAllPackages(BooleanValueObject $allPackages)
    {
        $this->allPackages = $allPackages;
    }

    /**
     * @return Collection
     */
    public function getPackageList()
    {
        return $this->packageList;
    }

    /**
     * @param Collection $packageList
     */
    public function setPackageList(Collection $packageList)
    {
        $this->packageList = $packageList;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllExtras()
    {
        return $this->allExtras;
    }

    /**
     * @param BooleanValueObject $allExtras
     */
    public function setAllExtras(BooleanValueObject $allExtras)
    {
        $this->allExtras = $allExtras;
    }

    /**
     * @return Collection
     */
    public function getExtraList()
    {
        return $this->extraList;
    }

    /**
     * @param Collection $extraList
     */
    public function setExtraList(Collection $extraList)
    {
        $this->extraList = $extraList;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'             => null !== $this->getId() ? $this->getId()->getValue() : null,
            'name'           => $this->getName()->getValue(),
            'amount'         => $this->getAmount()->getValue(),
            'type'           => $this->getType()->getValue(),
            'status'         => $this->getStatus()->getValue(),
            'excluded'       => $this->getExcluded() ? $this->getExcluded()->getValue() : null,
            'allServices'    => $this->getAllServices() ? $this->getAllServices()->getValue() : [],
            'allEvents'      => $this->getAllEvents() ? $this->getAllEvents()->getValue() : [],
            'allPackages'    => $this->getAllPackages() ? $this->getAllPackages()->getValue() : [],
            'allExtras'      => $this->getAllExtras() ? $this->getAllExtras()->getValue() : [],
            'serviceList'    => $this->getServiceList() ? $this->getServiceList()->toArray() : [],
            'eventList'      => $this->getEventList() ? $this->getEventList()->toArray() : [],
            'packageList'    => $this->getPackageList() ? $this->getPackageList()->toArray() : [],
            'extraList'      => $this->getExtraList() ? $this->getExtraList()->toArray() : [],
        ];
    }
}
