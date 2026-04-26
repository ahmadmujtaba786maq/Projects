<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Coupon;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\DiscountFixedValue;
use AmeliaBooking\Domain\ValueObjects\DiscountPercentageValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\CouponCode;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class Coupon
 *
 * @package AmeliaBooking\Domain\Entity\Coupon
 */
class Coupon
{
    /** @var Id */
    private $id;

    /** @var CouponCode */
    private $code;

    /** @var DiscountPercentageValue */
    private $discount;

    /** @var DiscountFixedValue */
    private $deduction;

    /** @var PositiveInteger */
    private $limit;

    /** @var WholeNumber */
    private $customerLimit;

    /** @var WholeNumber */
    private $used;

    /** @var WholeNumber */
    private $notificationInterval;

    /** @var BooleanValueObject */
    private $notificationRecurring;

    /** @var Status */
    private $status;

    /** @var Collection */
    private $serviceList;

    /** @var Collection */
    private $eventList;

    /** @var Collection */
    private $packageList;

    /** @var DateTimeValue */
    private $expirationDate;

    /** @var BooleanValueObject */
    private $allServices;

    /** @var BooleanValueObject */
    private $allEvents;


    /** @var BooleanValueObject */
    private $allPackages;

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
     * @return CouponCode
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param CouponCode $code
     */
    public function setCode(CouponCode $code)
    {
        $this->code = $code;
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
     * @return DiscountFixedValue
     */
    public function getDeduction()
    {
        return $this->deduction;
    }

    /**
     * @param DiscountFixedValue $deduction
     */
    public function setDeduction(DiscountFixedValue $deduction)
    {
        $this->deduction = $deduction;
    }

    /**
     * @return PositiveInteger
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param PositiveInteger $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return WholeNumber
     */
    public function getCustomerLimit()
    {
        return $this->customerLimit;
    }

    /**
     * @param WholeNumber $customerLimit
     */
    public function setCustomerLimit($customerLimit)
    {
        $this->customerLimit = $customerLimit;
    }

    /**
     * @return WholeNumber
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * @param WholeNumber $used
     */
    public function setUsed($used)
    {
        $this->used = $used;
    }

    /**
     * @return WholeNumber
     */
    public function getNotificationInterval()
    {
        return $this->notificationInterval;
    }

    /**
     * @param WholeNumber $notificationInterval
     */
    public function setNotificationInterval($notificationInterval)
    {
        $this->notificationInterval = $notificationInterval;
    }

    /**
     * @return BooleanValueObject
     */
    public function getNotificationRecurring()
    {
        return $this->notificationRecurring;
    }

    /**
     * @param BooleanValueObject $notificationRecurring
     */
    public function setNotificationRecurring($notificationRecurring)
    {
        $this->notificationRecurring = $notificationRecurring;
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
     * @return DateTimeValue
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param DateTimeValue $expirationDate
     */
    public function setExpirationDate(DateTimeValue $expirationDate)
    {
        $this->expirationDate = $expirationDate;
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
    public function setAllServices($allServices)
    {
        $this->allServices = $allServices;
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
    public function setAllEvents($allEvents)
    {
        $this->allEvents = $allEvents;
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
    public function setAllPackages($allPackages)
    {
        $this->allPackages = $allPackages;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                    => null !== $this->getId() ? $this->getId()->getValue() : null,
            'code'                  => $this->getCode() ? $this->getCode()->getValue() : null,
            'discount'              => $this->getDiscount() ? $this->getDiscount()->getValue() : null,
            'deduction'             => $this->getDeduction() ? $this->getDeduction()->getValue() : null,
            'limit'                 => $this->getLimit() ? $this->getLimit()->getValue() : null,
            'customerLimit'         => $this->getCustomerLimit() ? $this->getCustomerLimit()->getValue() : 0,
            'used'                  => $this->getUsed() ? $this->getUsed()->getValue() : 0,
            'notificationInterval'  => $this->getNotificationInterval() ? $this->getNotificationInterval()->getValue() : 0,
            'notificationRecurring' => $this->getNotificationRecurring() ? $this->getNotificationRecurring()->getValue() : 0,
            'status'                => $this->getStatus() ? $this->getStatus()->getValue() : null,
            'serviceList'           => $this->getServiceList() ? $this->getServiceList()->toArray() : [],
            'eventList'             => $this->getEventList() ? $this->getEventList()->toArray() : [],
            'packageList'           => $this->getPackageList() ? $this->getPackageList()->toArray() : [],
            'expirationDate'        => $this->getExpirationDate() ? $this->getExpirationDate()->getValue()->format('Y-m-d') : null,
            'allServices'           => $this->getAllServices() ? $this->getAllServices()->getValue() : 0,
            'allEvents'             => $this->getAllEvents() ? $this->getAllEvents()->getValue() : 0,
            'allPackages'           => $this->getAllPackages() ? $this->getAllPackages()->getValue() : 0,
        ];
    }
}
