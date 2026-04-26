<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Event;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\Recurring;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\Json;

/**
 * Class Event
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Event
 */
class Event extends AbstractBookable
{
    /** @var  Id */
    protected $parentId;

    /** @var  BookingStatus */
    protected $status;

    /** @var  Collection */
    protected $bookings;

    /** @var DateTimeValue */
    protected $bookingOpens;

    /** @var DateTimeValue */
    protected $bookingCloses;

    /** @var string */
    protected $bookingOpensRec;

    /** @var string */
    protected $bookingClosesRec;

    /** @var string */
    protected $ticketRangeRec;

    /** @var Recurring */
    private $recurring;

    /** @var IntegerValue */
    private $maxCapacity;

    /** @var BooleanValueObject */
    private $show;

    /** @var  Collection */
    protected $periods;

    /** @var Collection */
    private $tags;

    /** @var Collection */
    private $gallery;

    /** @var Collection */
    private $providers;

    /** @var bool */
    protected $notifyParticipants;

    /** @var Id */
    protected $locationId;

    /** @var Location */
    private $location;

    /** @var Name */
    protected $customLocation;

    /** @var DateTimeValue */
    protected $created;

    /** @var Name */
    private $zoomUserId;

    /** @var Id */
    private $organizerId;

    /** @var BooleanValueObject */
    private $bringingAnyone;

    /** @var BooleanValueObject */
    private $bookMultipleTimes;

    /** @var  Json */
    protected $translations;

    /** @var Collection */
    private $customTickets;

    /** @var BooleanValueObject */
    private $customPricing;

    /** @var IntegerValue */
    private $maxCustomCapacity;

    /** @var IntegerValue */
    private $closeAfterMin;

    /** @var BooleanValueObject */
    private $closeAfterMinBookings;

    /** @var  BooleanValueObject */
    private $aggregatedPrice;

    /** @var IntegerValue */
    private $maxExtraPeople;

    /** @var DateTimeValue|null */
    private $initialEventStart;

    /** @var DateTimeValue|null */
    private $initialEventEnd;

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
     * @return Recurring
     */
    public function getRecurring()
    {
        return $this->recurring;
    }

    /**
     * @param Recurring $recurring
     */
    public function setRecurring(Recurring $recurring)
    {
        $this->recurring = $recurring;
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
     * @return Collection
     */
    public function getBookings()
    {
        return $this->bookings;
    }

    /**
     * @param Collection $bookings
     */
    public function setBookings(Collection $bookings)
    {
        $this->bookings = $bookings;
    }

    /**
     * @return Collection
     */
    public function getPeriods()
    {
        return $this->periods;
    }

    /**
     * @param Collection $periods
     */
    public function setPeriods(Collection $periods)
    {
        $this->periods = $periods;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingOpens()
    {
        return $this->bookingOpens;
    }

    /**
     * @param DateTimeValue|null $bookingOpens
     */
    public function setBookingOpens(DateTimeValue $bookingOpens = null)
    {
        $this->bookingOpens = $bookingOpens;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingCloses()
    {
        return $this->bookingCloses;
    }

    /**
     * @param DateTimeValue|null $bookingCloses
     */
    public function setBookingCloses(DateTimeValue $bookingCloses = null)
    {
        $this->bookingCloses = $bookingCloses;
    }

    /**
     * @return string
     */
    public function getBookingOpensRec()
    {
        return $this->bookingOpensRec;
    }

    /**
     * @param string $bookingOpensRec
     */
    public function setBookingOpensRec($bookingOpensRec)
    {
        $this->bookingOpensRec = $bookingOpensRec;
    }

    /**
     * @return string
     */
    public function getBookingClosesRec()
    {
        return $this->bookingClosesRec;
    }

    /**
     * @param string $bookingClosesRec
     */
    public function setBookingClosesRec($bookingClosesRec)
    {
        $this->bookingClosesRec = $bookingClosesRec;
    }

    /**
     * @return string
     */
    public function getTicketRangeRec()
    {
        return $this->ticketRangeRec;
    }

    /**
     * @param string $ticketRangeRec
     */
    public function setTicketRangeRec($ticketRangeRec)
    {
        $this->ticketRangeRec = $ticketRangeRec;
    }

    /**
     * @return BookingStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param BookingStatus $status
     */
    public function setStatus(BookingStatus $status)
    {
        $this->status = $status;
    }

    /**
     * @return Collection
     */
    public function getCustomTickets()
    {
        return $this->customTickets;
    }

    /**
     * @param Collection $customTickets
     */
    public function setCustomTickets(Collection $customTickets)
    {
        $this->customTickets = $customTickets;
    }

    /**
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Collection $tags
     */
    public function setTags(Collection $tags)
    {
        $this->tags = $tags;
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
     * @return bool
     */
    public function isNotifyParticipants()
    {
        return $this->notifyParticipants;
    }

    /**
     * @param bool $notifyParticipants
     */
    public function setNotifyParticipants($notifyParticipants)
    {
        $this->notifyParticipants = $notifyParticipants;
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
     * @return Name
     */
    public function getCustomLocation()
    {
        return $this->customLocation;
    }

    /**
     * @param Name $customLocation
     */
    public function setCustomLocation(Name $customLocation)
    {
        $this->customLocation = $customLocation;
    }

    /**
     * @return DateTimeValue
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param DateTimeValue $created
     */
    public function setCreated(DateTimeValue $created)
    {
        $this->created = $created;
    }

    /**
     * @return Name
     */
    public function getZoomUserId()
    {
        return $this->zoomUserId;
    }

    /**
     * @param Name $zoomUserId
     */
    public function setZoomUserId(Name $zoomUserId)
    {
        $this->zoomUserId = $zoomUserId;
    }

    /**
     * @return Id
     */
    public function getOrganizerId()
    {
        return $this->organizerId;
    }

    /**
     * @param Id $organizerId
     */
    public function setOrganizerId($organizerId)
    {
        $this->organizerId = $organizerId;
    }


    /**
     * @return BookableType
     */
    public function getType()
    {
        return new BookableType(Entities::EVENT);
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
    public function getBookMultipleTimes()
    {
        return $this->bookMultipleTimes;
    }

    /**
     * @param BooleanValueObject $bookMultipleTimes
     */
    public function setBookMultipleTimes(BooleanValueObject $bookMultipleTimes)
    {
        $this->bookMultipleTimes = $bookMultipleTimes;
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
     * @return BooleanValueObject
     */
    public function getCustomPricing()
    {
        return $this->customPricing;
    }

    /**
     * @param BooleanValueObject $customPricing
     */
    public function setCustomPricing($customPricing)
    {
        $this->customPricing = $customPricing;
    }

    /**
     * @return IntegerValue
     */
    public function getMaxCustomCapacity()
    {
        return $this->maxCustomCapacity;
    }

    /**
     * @param IntegerValue $maxCustomCapacity
     */
    public function setMaxCustomCapacity($maxCustomCapacity)
    {
        $this->maxCustomCapacity = $maxCustomCapacity;
    }

    /**
     * @return IntegerValue
     */
    public function getCloseAfterMin()
    {
        return $this->closeAfterMin;
    }

    /**
     * @param IntegerValue $closeAfterMin
     */
    public function setCloseAfterMin($closeAfterMin)
    {
        $this->closeAfterMin = $closeAfterMin;
    }

    /**
     * @return BooleanValueObject
     */
    public function getCloseAfterMinBookings()
    {
        return $this->closeAfterMinBookings;
    }

    /**
     * @param BooleanValueObject $closeAfterMinBookings
     */
    public function setCloseAfterMinBookings($closeAfterMinBookings)
    {
        $this->closeAfterMinBookings = $closeAfterMinBookings;
    }

    /**
     * @return IntegerValue
     */
    public function getMaxExtraPeople()
    {
        return $this->maxExtraPeople;
    }

    /**
     * @return DateTimeValue|null
     */
    public function getInitialEventStart()
    {
        return $this->initialEventStart;
    }

    /**
     * @param DateTimeValue|null $initialEventStart
     */
    public function setInitialEventStart($initialEventStart)
    {
        $this->initialEventStart = $initialEventStart;
    }

    /**
     * @return DateTimeValue|null
     */
    public function getInitialEventEnd()
    {
        return $this->initialEventEnd;
    }

    /**
     * @param DateTimeValue|null $initialEventEnd
     */
    public function setInitialEventEnd($initialEventEnd)
    {
        $this->initialEventEnd = $initialEventEnd;
    }


    /**
     * @param IntegerValue $maxExtraPeople
     */
    public function setMaxExtraPeople($maxExtraPeople)
    {
        $this->maxExtraPeople = $maxExtraPeople;
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
    public function setAggregatedPrice($aggregatedPrice)
    {
        $this->aggregatedPrice = $aggregatedPrice;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'bookings'               => $this->getBookings() ? $this->getBookings()->toArray() : [],
                'periods'                => $this->getPeriods()->toArray(),
                'bookingOpens'           => $this->getBookingOpens() ?
                    $this->getBookingOpens()->getValue()->format('Y-m-d H:i:s') : null,
                'bookingCloses'          => $this->getBookingCloses() ?
                    $this->getBookingCloses()->getValue()->format('Y-m-d H:i:s') : null,
                'bookingOpensRec'        => $this->getBookingOpensRec(),
                'bookingClosesRec'       => $this->getBookingClosesRec(),
                'ticketRangeRec'         => $this->getTicketRangeRec(),
                'status'                 => $this->getStatus() ? $this->getStatus()->getValue() : null,
                'recurring'              => $this->getRecurring() ? $this->getRecurring()->toArray() : null,
                'maxCapacity'            => $this->getMaxCapacity() ? $this->getMaxCapacity()->getValue() : null,
                'maxCustomCapacity'      => $this->getMaxCustomCapacity() ? $this->getMaxCustomCapacity()->getValue() : null,
                'show'                   => $this->getShow() ? $this->getShow()->getValue() : null,
                'tags'                   => $this->getTags() ? $this->getTags()->toArray() : null,
                'customTickets'          => $this->getCustomTickets() ? $this->getCustomTickets()->toArray() : [],
                'gallery'                => $this->getGallery() ? $this->getGallery()->toArray() : [],
                'providers'              => $this->getProviders() ? $this->getProviders()->toArray() : [],
                'notifyParticipants'     => $this->isNotifyParticipants(),
                'locationId'             => $this->getLocationId() ? $this->getLocationId()->getValue() : null,
                'location'               => $this->getLocation() ? $this->getLocation()->toArray() : null,
                'customLocation'         => $this->getCustomLocation() ? $this->getCustomLocation()->getValue() : null,
                'parentId'               => $this->getParentId() ? $this->getParentId()->getValue() : null,
                'created'                => $this->getCreated() ? $this->getCreated()->getValue()->format('Y-m-d H:i:s') : null,
                'zoomUserId'             => $this->getZoomUserId() ? $this->getZoomUserId()->getValue() : null,
                'organizerId'            => $this->getOrganizerId() ? $this->getOrganizerId()->getValue() : null,
                'type'                   => $this->getType()->getValue(),
                'bringingAnyone'         => $this->getBringingAnyone() ? $this->getBringingAnyone()->getValue() : null,
                'bookMultipleTimes'      => $this->getBookMultipleTimes() ? $this->getBookMultipleTimes()->getValue() : null,
                'translations'           => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
                'customPricing'          => $this->getCustomPricing() ? $this->getCustomPricing()->getValue() : null,
                'closeAfterMin'          => $this->getCloseAfterMin() ? $this->getCloseAfterMin()->getValue() : null,
                'closeAfterMinBookings'  => $this->getCloseAfterMinBookings() ? $this->getCloseAfterMinBookings()->getValue() : null,
                'maxExtraPeople'         => $this->getMaxExtraPeople() ? $this->getMaxExtraPeople()->getValue() : null,
                'initialEventStart'      => $this->getInitialEventStart() ? $this->getInitialEventStart()->getValue()->format('Y-m-d H:i:s') : null,
                'initialEventEnd'        => $this->getInitialEventEnd() ? $this->getInitialEventEnd()->getValue()->format('Y-m-d H:i:s') : null,
                'aggregatedPrice'        => $this->getAggregatedPrice() ? $this->getAggregatedPrice()->getValue() : null,
            ]
        );
    }
}
