<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Appointment;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\AbstractBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Entity\Zoom\ZoomMeeting;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class Appointment
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Appointment
 */
class Appointment extends AbstractBooking
{
    /** @var Id */
    private $parentId;

    /** @var Id */
    private $serviceId;

    /** @var Service */
    private $service;

    /** @var Id */
    private $providerId;

    /** @var Provider */
    private $provider;

    /** @var Id */
    private $locationId;

    /** @var Location */
    private $location;

    /** @var Token */
    private $googleCalendarEventId;

    /** @var string */
    private $googleMeetUrl;

    /** @var Label */
    private $outlookCalendarEventId;

    /** @var Label */
    private $appleCalendarEventId;

    /** @var string */
    private $microsoftTeamsUrl;

    /** @var DateTimeValue */
    protected $initialBookingStart;

    /** @var DateTimeValue */
    protected $initialBookingEnd;

    /** @var DateTimeValue */
    protected $bookingStart;

    /** @var DateTimeValue */
    protected $bookingEnd;

    /** @var ZoomMeeting */
    private $zoomMeeting;

    /** @var string */
    private $lessonSpace;

    /** @var Collection */
    private $resources;

    /** @var  BooleanValueObject */
    protected $isRescheduled;

    /** @var  BooleanValueObject */
    protected $isChangedStatus;

    /** @var  BooleanValueObject */
    protected $isFull;

    /** @var  bool */
    protected $createPaymentLinks;

    /**
     * Appointment constructor.
     *
     * @param DateTimeValue $bookingStart
     * @param DateTimeValue $bookingEnd
     * @param bool          $notifyParticipants
     * @param Id            $serviceId
     * @param Id            $providerId
     */
    public function __construct(
        DateTimeValue $bookingStart,
        DateTimeValue $bookingEnd,
        $notifyParticipants,
        Id $serviceId,
        Id $providerId
    ) {
        parent::__construct($notifyParticipants);

        $this->bookingStart = $bookingStart;

        $this->bookingEnd = $bookingEnd;

        $this->serviceId = $serviceId;

        $this->providerId = $providerId;

        $this->resources = new Collection();
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
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param Provider $provider
     */
    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
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
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;
    }

    /**
     * @return Token
     */
    public function getGoogleCalendarEventId()
    {
        return $this->googleCalendarEventId;
    }

    /**
     * @param Token|null $googleCalendarEventId
     */
    public function setGoogleCalendarEventId($googleCalendarEventId)
    {
        $this->googleCalendarEventId = $googleCalendarEventId;
    }

    /**
     * @return string
     */
    public function getGoogleMeetUrl()
    {
        return $this->googleMeetUrl;
    }

    /**
     * @param string $googleMeetUrl
     */
    public function setGoogleMeetUrl($googleMeetUrl)
    {
        $this->googleMeetUrl = $googleMeetUrl;
    }

    /**
     * @return Label
     */
    public function getOutlookCalendarEventId()
    {
        return $this->outlookCalendarEventId;
    }

    /**
     * @param Label|null $outlookCalendarEventId
     */
    public function setOutlookCalendarEventId($outlookCalendarEventId)
    {
        $this->outlookCalendarEventId = $outlookCalendarEventId;
    }

    /**
     * @return Label
     */
    public function getAppleCalendarEventId()
    {
        return $this->appleCalendarEventId;
    }

    /**
     * @param Label|null $appleCalendarEventId
     */
    public function setAppleCalendarEventId($appleCalendarEventId)
    {
        $this->appleCalendarEventId = $appleCalendarEventId;
    }

    /**
     * @return string
     */
    public function getMicrosoftTeamsUrl()
    {
        return $this->microsoftTeamsUrl;
    }

    /**
     * @param string $microsoftTeamsUrl
     */
    public function setMicrosoftTeamsUrl($microsoftTeamsUrl)
    {
        $this->microsoftTeamsUrl = $microsoftTeamsUrl;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingStart()
    {
        return $this->bookingStart;
    }

    /**
     * @param DateTimeValue $bookingStart
     */
    public function setBookingStart(DateTimeValue $bookingStart)
    {
        $this->bookingStart = $bookingStart;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingEnd()
    {
        return $this->bookingEnd;
    }

    /**
     * @param DateTimeValue $bookingEnd
     */
    public function setBookingEnd(DateTimeValue $bookingEnd)
    {
        $this->bookingEnd = $bookingEnd;
    }

    /**
     * @return DateTimeValue
     */
    public function getInitialBookingStart()
    {
        return $this->initialBookingStart;
    }

    /**
     * @param DateTimeValue $initialBookingStart
     */
    public function setInitialBookingStart(DateTimeValue $initialBookingStart)
    {
        $this->initialBookingStart = $initialBookingStart;
    }

    /**
     * @return DateTimeValue
     */
    public function getInitialBookingEnd()
    {
        return $this->initialBookingEnd;
    }

    /**
     * @param DateTimeValue $initialBookingEnd
     */
    public function setInitialBookingEnd(DateTimeValue $initialBookingEnd)
    {
        $this->initialBookingEnd = $initialBookingEnd;
    }

    /**
     * @return BookingType
     */
    public function getType()
    {
        return new Bookingtype(Entities::APPOINTMENT);
    }

    /**
     * @return ZoomMeeting
     */
    public function getZoomMeeting()
    {
        return $this->zoomMeeting;
    }

    /**
     * @param ZoomMeeting $zoomMeeting
     */
    public function setZoomMeeting(ZoomMeeting $zoomMeeting)
    {
        $this->zoomMeeting = $zoomMeeting;
    }

    /**
     * @return string
     */
    public function getLessonSpace()
    {
        return $this->lessonSpace;
    }

    /**
     * @param string $lessonSpace
     */
    public function setLessonSpace($lessonSpace)
    {
        $this->lessonSpace = $lessonSpace;
    }

    /**
     * @return Id
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param Id $parentId
     */
    public function setParentId(Id $parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * @return BooleanValueObject
     */
    public function isRescheduled()
    {
        return $this->isRescheduled;
    }

    /**
     * @param BooleanValueObject $isRescheduled
     */
    public function setRescheduled(BooleanValueObject $isRescheduled)
    {
        $this->isRescheduled = $isRescheduled;
    }

    /**
     * @return BooleanValueObject
     */
    public function isChangedStatus()
    {
        return $this->isChangedStatus;
    }

    /**
     * @param BooleanValueObject $isChangedStatus
     */
    public function setChangedStatus(BooleanValueObject $isChangedStatus)
    {
        $this->isChangedStatus = $isChangedStatus;
    }

    /**
     * @return BooleanValueObject
     */
    public function isFull()
    {
        return $this->isFull;
    }

    /**
     * @param BooleanValueObject $isFull
     */
    public function setFull(BooleanValueObject $isFull)
    {
        $this->isFull = $isFull;
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

    public function isCreatePaymentLinks()
    {
        return $this->createPaymentLinks;
    }

    public function setCreatePaymentLinks($createPaymentLinks)
    {
        $this->createPaymentLinks = $createPaymentLinks;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'serviceId'              => $this->getServiceId()->getValue(),
                'parentId'               => $this->getParentId() ? $this->getParentId()->getValue() : null,
                'providerId'             => $this->getProviderId()->getValue(),
                'locationId'             => null !== $this->getLocationId() ? $this->getLocationId()->getValue() : null,
                'provider'               => null !== $this->getProvider() ? $this->getProvider()->toArray() : null,
                'service'                => null !== $this->getService() ? $this->getService()->toArray() : null,
                'location'               => null !== $this->getLocation() ? $this->getLocation()->toArray() : null,
                'googleCalendarEventId'  => null !== $this->getGoogleCalendarEventId() ?
                    $this->getGoogleCalendarEventId()->getValue() : null,
                'googleMeetUrl'          => null !== $this->getGoogleMeetUrl() ? $this->getGoogleMeetUrl() : null,
                'outlookCalendarEventId' => null !== $this->getOutlookCalendarEventId() ?
                    $this->getOutlookCalendarEventId()->getValue() : null,
                'microsoftTeamsUrl'      => null !== $this->getMicrosoftTeamsUrl() ? $this->getMicrosoftTeamsUrl() : null,
                'appleCalendarEventId'   => null !== $this->getAppleCalendarEventId() ?
                    $this->getAppleCalendarEventId()->getValue() : null,
                'zoomMeeting'            => $this->getZoomMeeting() ? $this->getZoomMeeting()->toArray() : null,
                'lessonSpace'            => $this->getLessonSpace() ?: null,
                'bookingStart'           => $this->getBookingStart()->getValue()->format('Y-m-d H:i:s'),
                'bookingEnd'             => $this->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                'type'                   => $this->getType()->getValue(),
                'isRescheduled'          => $this->isRescheduled() ? $this->isRescheduled()->getValue() : null,
                'isChangedStatus'        => $this->isChangedStatus() ? $this->isChangedStatus()->getValue() : null,
                'isFull'                 => $this->isFull() ? $this->isFull()->getValue() : null,
                'resources'              => $this->getResources()->toArray(),
                'initialAppointmentDateTime' => $this->getInitialBookingStart() && $this->getInitialBookingEnd()
                    ? [
                        'bookingEnd' => $this->getInitialBookingStart()
                            ? $this->getInitialBookingStart()->getValue()->format('Y-m-d H:i:s')
                            : null,
                        'bookingStart' => $this->getInitialBookingEnd()
                            ? $this->getInitialBookingEnd()->getValue()->format('Y-m-d H:i:s')
                            : null
                    ]
                    : null,
                'createPaymentLinks' => $this->isCreatePaymentLinks(),
            ]
        );
    }
}
