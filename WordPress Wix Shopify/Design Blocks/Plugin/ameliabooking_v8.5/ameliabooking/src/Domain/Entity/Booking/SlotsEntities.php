<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking;

use AmeliaBooking\Domain\Collection\Collection;

/**
 * Class SlotsEntities
 *
 * @package AmeliaBooking\Domain\Entity\Booking
 */
class SlotsEntities
{
    /** @var Collection */
    private $services;

    /** @var Collection */
    private $providers;

    /** @var Collection */
    private $locations;

    /** @var Collection */
    private $resources;

    /**
     * @return Collection
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @param Collection $services
     */
    public function setServices(Collection $services)
    {
        $this->services = $services;
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
     * @return Collection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param Collection $resources
     */
    public function setResources(Collection $resources)
    {
        $this->resources = $resources;
    }
}
