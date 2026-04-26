<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Event;

use AmeliaBooking\Domain\Entity\Booking\Event\EventTag;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class EventTagFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Event
 */
class EventTagFactory
{
    /**
     * @param $data
     *
     * @return EventTag
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function create($data)
    {
        $eventTag = new EventTag();

        if (!empty($data['id'])) {
            $eventTag->setId(new Id($data['id']));
        }

        if (!empty($data['eventId'])) {
            $eventTag->setEventId(new Id($data['eventId']));
        }

        if (isset($data['name'])) {
            $eventTag->setName(new Name($data['name']));
        }

        return $eventTag;
    }
}
