<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Event;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Url;
use AmeliaBooking\Domain\Factory\Zoom\ZoomFactory;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class EventPeriodFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Event
 */
class EventPeriodFactory
{
    /**
     * @param $data
     *
     * @return EventPeriod
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $eventPeriod = new EventPeriod();

        if (!empty($data['id'])) {
            $eventPeriod->setId(new Id($data['id']));
        }

        if (!empty($data['eventId'])) {
            $eventPeriod->setEventId(new Id($data['eventId']));
        }

        if (isset($data['periodStart'])) {
            $eventPeriod->setPeriodStart(new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['periodStart'])));
        }

        if (isset($data['periodEnd'])) {
            $eventPeriod->setPeriodEnd(new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['periodEnd'])));
        }

        if (!empty($data['zoomMeeting']) && !empty($data['zoomMeeting']['id'])) {
            $zoomMeeting = ZoomFactory::create(
                $data['zoomMeeting']
            );

            $eventPeriod->setZoomMeeting($zoomMeeting);
        }

        if (isset($data['lessonSpace']) && !empty($data['lessonSpace'])) {
            $eventPeriod->setLessonSpace($data['lessonSpace']);
        }

        if (!empty($data['googleCalendarEventId'])) {
            $eventPeriod->setGoogleCalendarEventId(new Token($data['googleCalendarEventId']));
        }

        if (!empty($data['googleMeetUrl'])) {
            $eventPeriod->setGoogleMeetUrl($data['googleMeetUrl']);
        }

        if (!empty($data['outlookCalendarEventId'])) {
            $eventPeriod->setOutlookCalendarEventId(new Label($data['outlookCalendarEventId']));
        }

        if (!empty($data['microsoftTeamsUrl'])) {
            $eventPeriod->setMicrosoftTeamsUrl($data['microsoftTeamsUrl']);
        }

        if (!empty($data['appleCalendarEventId'])) {
            $eventPeriod->setAppleCalendarEventId(new Label($data['appleCalendarEventId']));
        }

        return $eventPeriod;
    }
}
