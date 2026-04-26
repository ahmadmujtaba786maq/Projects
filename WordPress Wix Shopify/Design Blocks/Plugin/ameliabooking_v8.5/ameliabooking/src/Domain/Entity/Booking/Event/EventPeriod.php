<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Event;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\Entity\Zoom\ZoomMeeting;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Domain\ValueObjects\String\Label;

/**
 * Class EventPeriod
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Event
 */
class EventPeriod
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $eventId;

    /** @var  DateTimeValue */
    protected $periodStart;

    /** @var DateTimeValue */
    protected $periodEnd;

    /** @var ZoomMeeting */
    private $zoomMeeting;

    /** @var string */
    private $lessonSpace;

    /** @var  Collection */
    protected $bookings;

    /** @var Token */
    private $googleCalendarEventId;

    /** @var string */
    private $googleMeetUrl;

    /** @var Label */
    private $outlookCalendarEventId;

    /** @var string */
    private $microsoftTeamsUrl;

    /** @var Label */
    private $appleCalendarEventId;

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
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param Id $eventId
     */
    public function setEventId(Id $eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return DateTimeValue
     */
    public function getPeriodStart()
    {
        return $this->periodStart;
    }

    /**
     * @param DateTimeValue $periodStart
     */
    public function setPeriodStart(DateTimeValue $periodStart)
    {
        $this->periodStart = $periodStart;
    }

    /**
     * @return DateTimeValue
     */
    public function getPeriodEnd()
    {
        return $this->periodEnd;
    }

    /**
     * @param DateTimeValue $periodEnd
     */
    public function setPeriodEnd(DateTimeValue $periodEnd)
    {
        $this->periodEnd = $periodEnd;
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
     * @return Token
     */
    public function getGoogleCalendarEventId()
    {
        return $this->googleCalendarEventId;
    }

    /**
     * @param Token $googleCalendarEventId
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
     * @param Label $outlookCalendarEventId
     */
    public function setOutlookCalendarEventId($outlookCalendarEventId)
    {
        $this->outlookCalendarEventId = $outlookCalendarEventId;
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
     * @return Label
     */
    public function getAppleCalendarEventId()
    {
        return $this->appleCalendarEventId;
    }

    /**
     * @param Label $appleCalendarEventId
     */
    public function setAppleCalendarEventId($appleCalendarEventId)
    {
        $this->appleCalendarEventId = $appleCalendarEventId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                     => $this->getId() ? $this->getId()->getValue() : null,
            'eventId'                => $this->getEventId() ? $this->getEventId()->getValue() : null,
            'periodStart'            => $this->getPeriodStart()->getValue()->format('Y-m-d H:i:s'),
            'periodEnd'              => $this->getPeriodEnd()->getValue()->format('Y-m-d H:i:s'),
            'zoomMeeting'            => $this->getZoomMeeting() ? $this->getZoomMeeting()->toArray() : null,
            'lessonSpace'            => $this->getLessonSpace() ?: null,
            'bookings'               => $this->getBookings() ? $this->getBookings()->toArray() : [],
            'googleCalendarEventId'  => $this->getGoogleCalendarEventId() ? $this->getGoogleCalendarEventId()->getValue() : null,
            'googleMeetUrl'          => $this->getGoogleMeetUrl(),
            'outlookCalendarEventId' => $this->getOutlookCalendarEventId() ? $this->getOutlookCalendarEventId()->getValue() : null,
            'microsoftTeamsUrl'      => $this->getMicrosoftTeamsUrl(),
            'appleCalendarEventId'   => $this->getAppleCalendarEventId() ? $this->getAppleCalendarEventId()->getValue() : null,
        ];
    }
}
