<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;

/**
 * Class PackageService
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class PackageService
{
    /** @var Id */
    private $id;

    /** @var  Service */
    protected $service;

    /** @var  PositiveInteger */
    protected $quantity;

    /** @var  WholeNumber */
    protected $minimumScheduled;

    /** @var  WholeNumber */
    protected $maximumScheduled;

    /** @var Collection */
    private $providers;

    /** @var Collection */
    private $locations;

    /** @var BooleanValueObject */
    private $allowProviderSelection;

    /** @var PositiveInteger */
    protected $position;

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
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
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
     * @return WholeNumber
     */
    public function getMinimumScheduled()
    {
        return $this->minimumScheduled;
    }

    /**
     * @param WholeNumber $minimumScheduled
     */
    public function setMinimumScheduled(WholeNumber $minimumScheduled)
    {
        $this->minimumScheduled = $minimumScheduled;
    }

    /**
     * @return WholeNumber
     */
    public function getMaximumScheduled()
    {
        return $this->maximumScheduled;
    }

    /**
     * @param WholeNumber $maximumScheduled
     */
    public function setMaximumScheduled(WholeNumber $maximumScheduled)
    {
        $this->maximumScheduled = $maximumScheduled;
    }

    /**
     * @return Collection
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @param Collection $providers
     */
    public function setProviders(Collection $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @return Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @param Collection $locations
     */
    public function setLocations(Collection $locations)
    {
        $this->locations = $locations;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAllowProviderSelection()
    {
        return $this->allowProviderSelection;
    }

    /**
     * @param BooleanValueObject $allowProviderSelection
     */
    public function setAllowProviderSelection($allowProviderSelection)
    {
        $this->allowProviderSelection = $allowProviderSelection;
    }

    /**
     * @return PositiveInteger
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param PositiveInteger $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            [
                'id'               => $this->getId() ? $this->getId()->getValue() : null,
                'quantity'         => $this->getQuantity() ? $this->getQuantity()->getValue() : null,
                'service'          => $this->getService() ? $this->getService()->toArray() : null,
                'minimumScheduled' => $this->getMinimumScheduled() ? $this->getMinimumScheduled()->getValue() : null,
                'maximumScheduled' => $this->getMaximumScheduled() ? $this->getMaximumScheduled()->getValue() : null,
                'providers'        => $this->getProviders() ? $this->getProviders()->toArray() : [],
                'locations'        => $this->getLocations() ? $this->getLocations()->toArray() : [],
                'allowProviderSelection' => $this->getAllowProviderSelection() ? $this->getAllowProviderSelection()->getValue() : null,
                'position'         => null !== $this->getPosition() ? $this->getPosition()->getValue() : null,
            ]
        );
    }
}
