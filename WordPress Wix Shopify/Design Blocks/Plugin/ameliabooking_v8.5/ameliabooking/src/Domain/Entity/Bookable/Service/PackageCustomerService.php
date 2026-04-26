<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;

/**
 * Class PackageCustomerService
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class PackageCustomerService
{
    /** @var Id */
    private $id;

    /** @var PackageCustomer */
    private $packageCustomer;

    /** @var Id */
    private $serviceId;

    /** @var Id */
    private $providerId;

    /** @var Id */
    private $locationId;

    /** @var WholeNumber */
    private $bookingsCount;

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
     * @return Id
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * @param Id $providerId
     */
    public function setProviderId(Id $providerId)
    {
        $this->providerId = $providerId;
    }

    /**
     * @return Id
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param Id $locationId
     */
    public function setLocationId(Id $locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return WholeNumber
     */
    public function getBookingsCount()
    {
        return $this->bookingsCount;
    }

    /**
     * @param WholeNumber $bookingsCount
     */
    public function setBookingsCount(WholeNumber $bookingsCount)
    {
        $this->bookingsCount = $bookingsCount;
    }

    /**
     * @return PackageCustomer
     */
    public function getPackageCustomer()
    {
        return $this->packageCustomer;
    }

    /**
     * @param PackageCustomer $packageCustomer
     */
    public function setPackageCustomer(PackageCustomer $packageCustomer)
    {
        $this->packageCustomer = $packageCustomer;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                => $this->getId() ? $this->getId()->getValue() : null,
            'serviceId'         => $this->getServiceId() ? $this->getServiceId()->getValue() : null,
            'providerId'        => $this->getProviderId() ? $this->getProviderId()->getValue() : null,
            'locationId'        => $this->getLocationId() ? $this->getLocationId()->getValue() : null,
            'bookingsCount'     => $this->getBookingsCount() ? $this->getBookingsCount()->getValue() : null,
            'packageCustomer'   => $this->getPackageCustomer() ? $this->getPackageCustomer()->toArray() : null,
        ];
    }
}
