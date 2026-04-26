<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\Label;

/**
 * Class Reservation
 *
 * @package AmeliaBooking\Domain\Entity\Booking
 */
class Reservation
{
    /** @var Appointment|Event */
    private $reservation;

    /** @var CustomerBooking */
    private $booking;

    /** @var  AbstractBookable */
    private $bookable;

    /** @var Customer */
    private $customer;

    /** @var Label */
    private $locale;

    /** @var Label */
    private $timeZone;

    /** @var BooleanValueObject */
    private $isNewUser;

    /** @var BooleanValueObject */
    private $isStatusChanged;

    /** @var BooleanValueObject */
    private $isCart;

    /** @var BooleanValueObject */
    private $applyDeposit;

    /** @var array */
    private $uploadedCustomFieldFilesInfo;

    /** @var array */
    private $uploadedCustomerCustomFieldFilesInfo;

    /** @var Collection  */
    private $recurring;

    /** @var Collection  */
    private $packageReservations;

    /** @var Collection  */
    private $packageCustomerServices;

    /** @var PackageCustomer  */
    private $packageCustomer;

    /** @var BooleanValueObject  */
    private $couponValidation;

    /** @var BooleanValueObject  */
    private $customFieldsValidation;

    /** @var BooleanValueObject  */
    private $availabilityValidation;

    /** @var AbstractUser $user */
    private $loggedInUser;

    /**
     * @return BooleanValueObject
     */
    public function hasCouponValidation()
    {
        return $this->couponValidation;
    }

    /**
     * @param BooleanValueObject $couponValidation
     */
    public function setCouponValidation(BooleanValueObject $couponValidation)
    {
        $this->couponValidation = $couponValidation;
    }

    /**
     * @return BooleanValueObject
     */
    public function hasCustomFieldsValidation()
    {
        return $this->customFieldsValidation;
    }

    /**
     * @param BooleanValueObject $customFieldsValidation
     */
    public function setCustomFieldsValidation(BooleanValueObject $customFieldsValidation)
    {
        $this->customFieldsValidation = $customFieldsValidation;
    }

    /**
     * @return BooleanValueObject
     */
    public function hasAvailabilityValidation()
    {
        return $this->availabilityValidation;
    }

    /**
     * @param BooleanValueObject $availabilityValidation
     */
    public function setAvailabilityValidation(BooleanValueObject $availabilityValidation)
    {
        $this->availabilityValidation = $availabilityValidation;
    }

    /**
     * @return Appointment|Event
     */
    public function getReservation()
    {
        return $this->reservation;
    }

    /**
     * @param Appointment|Event|Package $reservation
     */
    public function setReservation($reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * @return CustomerBooking
     */
    public function getBooking()
    {
        return $this->booking;
    }

    /**
     * @param CustomerBooking $booking
     */
    public function setBooking(CustomerBooking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * @return AbstractBookable
     */
    public function getBookable()
    {
        return $this->bookable;
    }

    /**
     * @param Service|Event|Package $bookable
     */
    public function setBookable(AbstractBookable $bookable)
    {
        $this->bookable = $bookable;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @return Label
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param Label $locale
     */
    public function setLocale(Label $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return Label
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param Label $timeZone
     */
    public function setTimeZone(Label $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return Collection
     */
    public function getRecurring()
    {
        return $this->recurring;
    }

    /**
     * @param Collection $recurring
     */
    public function setRecurring(Collection $recurring)
    {
        $this->recurring = $recurring;
    }

    /**
     * @return Collection
     */
    public function getPackageReservations()
    {
        return $this->packageReservations;
    }

    /**
     * @param Collection $packageReservations
     */
    public function setPackageReservations(Collection $packageReservations)
    {
        $this->packageReservations = $packageReservations;
    }

    /**
     * @return Collection
     */
    public function getPackageCustomerServices()
    {
        return $this->packageCustomerServices;
    }

    /**
     * @param Collection $packageCustomerServices
     */
    public function setPackageCustomerServices(Collection $packageCustomerServices)
    {
        $this->packageCustomerServices = $packageCustomerServices;
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
     * @return BooleanValueObject
     */
    public function isNewUser()
    {
        return $this->isNewUser;
    }

    /**
     * @param BooleanValueObject $isNewUser
     */
    public function setIsNewUser(BooleanValueObject $isNewUser)
    {
        $this->isNewUser = $isNewUser;
    }

    /**
     * @return BooleanValueObject
     */
    public function isStatusChanged()
    {
        return $this->isStatusChanged;
    }

    /**
     * @param BooleanValueObject $isStatusChanged
     */
    public function setIsStatusChanged(BooleanValueObject $isStatusChanged)
    {
        $this->isStatusChanged = $isStatusChanged;
    }

    /**
     * @return BooleanValueObject
     */
    public function isCart()
    {
        return $this->isCart;
    }

    /**
     * @param BooleanValueObject $isCart
     */
    public function setIsCart(BooleanValueObject $isCart)
    {
        $this->isCart = $isCart;
    }

    /**
     * @return array
     */
    public function getUploadedCustomFieldFilesInfo()
    {
        return $this->uploadedCustomFieldFilesInfo;
    }

    /**
     * @param array $uploadedCustomFieldFilesInfo
     */
    public function setUploadedCustomFieldFilesInfo(array $uploadedCustomFieldFilesInfo)
    {
        $this->uploadedCustomFieldFilesInfo = $uploadedCustomFieldFilesInfo;
    }

    /**
     * @return array
     */
    public function getUploadedCustomerCustomFieldFilesInfo()
    {
        return $this->uploadedCustomerCustomFieldFilesInfo;
    }

    /**
     * @param array $uploadedCustomerCustomFieldFilesInfo
     */
    public function setUploadedCustomerCustomFieldFilesInfo(array $uploadedCustomerCustomFieldFilesInfo)
    {
        $this->uploadedCustomerCustomFieldFilesInfo = $uploadedCustomerCustomFieldFilesInfo;
    }

    /**
     * @return BooleanValueObject
     */
    public function getApplyDeposit()
    {
        return $this->applyDeposit;
    }

    /**
     * @param BooleanValueObject $applyDeposit
     */
    public function setApplyDeposit(BooleanValueObject $applyDeposit)
    {
        $this->applyDeposit = $applyDeposit;
    }

    /**
     * @return AbstractUser
     */
    public function getLoggedInUser()
    {
        return $this->loggedInUser;
    }

    /**
     * @param AbstractUser $loggedInUser
     */
    public function setLoggedInUser(AbstractUser $loggedInUser)
    {
        $this->loggedInUser = $loggedInUser;
    }
}
