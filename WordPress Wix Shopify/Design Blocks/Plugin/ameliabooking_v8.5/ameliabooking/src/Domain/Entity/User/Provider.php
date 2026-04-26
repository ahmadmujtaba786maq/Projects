<?php

namespace AmeliaBooking\Domain\Entity\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Google\GoogleCalendar;
use AmeliaBooking\Domain\Entity\Outlook\OutlookCalendar;
use AmeliaBooking\Domain\Entity\Stripe\StripeConnect;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Email;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Phone;

/**
 * Class Provider
 *
 * @package AmeliaBooking\Domain\Entity\User
 */
class Provider extends AbstractUser
{
    /** @var Collection */
    private $weekDayList;

    /** @var Collection */
    private $serviceList;

    /** @var Collection */
    private $dayOffList;

    /** @var Collection */
    private $specialDayList;

    /** @var Collection */
    private $appointmentList;

    /** @var Id */
    private $locationId;

    /** @var GoogleCalendar */
    private $googleCalendar;

    /** @var OutlookCalendar */
    private $outlookCalendar;

    /** @var Name */
    private $timeZone;

    /** @var Description */
    private $description;

    /** @var Id */
    private $badgeId;

    /** @var StripeConnect */
    private $stripeConnect;

    /** @var AppleCalendarEmployeeConnect */
    private $employeeAppleCalendar;

    /** @var  BooleanValueObject */
    private $show;


    /**
     * @param Name       $firstName
     * @param Name       $lastName
     * @param Email      $email
     * @param Phone      $phone
     * @param Collection $weekDayList
     * @param Collection $serviceList
     * @param Collection $dayOffList
     * @param Collection $specialDayList
     * @param Collection $appointmentList
     */
    public function __construct(
        Name $firstName,
        Name $lastName,
        Email $email,
        Phone $phone,
        Collection $weekDayList,
        Collection $serviceList,
        Collection $dayOffList,
        Collection $specialDayList,
        Collection $appointmentList
    ) {
        parent::__construct($firstName, $lastName, $email);
        $this->phone           = $phone;
        $this->weekDayList     = $weekDayList;
        $this->serviceList     = $serviceList;
        $this->dayOffList      = $dayOffList;
        $this->specialDayList  = $specialDayList;
        $this->appointmentList = $appointmentList;
    }

    /**
     * Get the user type in a string form
     */
    public function getType()
    {
        return self::USER_ROLE_PROVIDER;
    }

    /**
     * @return Collection
     */
    public function getWeekDayList()
    {
        return $this->weekDayList;
    }

    /**
     * @param Collection $weekDayList
     */
    public function setWeekDayList(Collection $weekDayList)
    {
        $this->weekDayList = $weekDayList;
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
    public function getDayOffList()
    {
        return $this->dayOffList;
    }

    /**
     * @param Collection $dayOffList
     */
    public function setDayOffList(Collection $dayOffList)
    {
        $this->dayOffList = $dayOffList;
    }

    /**
     * @return Collection
     */
    public function getSpecialDayList()
    {
        return $this->specialDayList;
    }

    /**
     * @param Collection $specialDayList
     */
    public function setSpecialDayList(Collection $specialDayList)
    {
        $this->specialDayList = $specialDayList;
    }

    /**
     * @return Collection
     */
    public function getAppointmentList()
    {
        return $this->appointmentList;
    }

    /**
     * @param Collection $appointmentList
     */
    public function setAppointmentList(Collection $appointmentList)
    {
        $this->appointmentList = $appointmentList;
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
     * @return GoogleCalendar mixed
     */
    public function getGoogleCalendar()
    {
        return $this->googleCalendar;
    }

    /**
     * @param mixed $googleCalendar
     */
    public function setGoogleCalendar($googleCalendar)
    {
        $this->googleCalendar = $googleCalendar;
    }

    /**
     * @return OutlookCalendar mixed
     */
    public function getOutlookCalendar()
    {
        return $this->outlookCalendar;
    }

    /**
     * @param mixed $outlookCalendar
     */
    public function setOutlookCalendar($outlookCalendar)
    {
        $this->outlookCalendar = $outlookCalendar;
    }

    /**
     * @return Name
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param Name $timeZone
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Description $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return Id
     */
    public function getBadgeId()
    {
        return $this->badgeId;
    }

    /**
     * @param Id $badgeId
     */
    public function setBadgeId(Id $badgeId)
    {
        $this->badgeId = $badgeId;
    }

    /**
     * @return StripeConnect
     */
    public function getStripeConnect()
    {
        return $this->stripeConnect;
    }

    /**
     * @param StripeConnect $stripeConnect
     */
    public function setStripeConnect($stripeConnect)
    {
        $this->stripeConnect = $stripeConnect;
    }

    /**
     * @return AppleCalendarEmployeeConnect
     */
    public function getEmployeeAppleCalendar()
    {
        return $this->employeeAppleCalendar;
    }

    /**
     * @param AppleCalendarEmployeeConnect $employeeAppleCalendar
     */
    public function setEmployeeAppleCalendar($employeeAppleCalendar)
    {
        $this->employeeAppleCalendar = $employeeAppleCalendar;
    }

    public function getShow()
    {
        return $this->show;
    }

    public function setShow(BooleanValueObject $show)
    {
        $this->show = $show;
    }


    /**
     * Returns the Provider entity fields in an array form
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'phone'                 => $this->phone->getValue(),
                'weekDayList'           => $this->weekDayList->toArray(),
                'serviceList'           => $this->serviceList->toArray(),
                'dayOffList'            => $this->dayOffList->toArray(),
                'specialDayList'        => $this->specialDayList->toArray(),
                'locationId'            => $this->getLocationId() ? $this->getLocationId()->getValue() : null,
                'googleCalendar'        => $this->getGoogleCalendar() ? $this->getGoogleCalendar()->toArray() : null,
                'outlookCalendar'       => $this->getOutlookCalendar() ? $this->getOutlookCalendar()->toArray() : null,
                'timeZone'              => $this->getTimeZone() ? $this->getTimeZone()->getValue() : null,
                'description'           => $this->getDescription() ? $this->getDescription()->getValue() : null,
                'badgeId'               => $this->getBadgeId() ? $this->getBadgeId()->getValue() : null,
                'stripeConnect'         => $this->getStripeConnect() ? $this->getStripeConnect()->toArray() : null,
                'employeeAppleCalendar' => $this->getEmployeeAppleCalendar() ? $this->getEmployeeAppleCalendar()->toArray() : null,
                'show'                  => $this->getShow() ? $this->getShow()->getValue() : null,
            ]
        );
    }
}
