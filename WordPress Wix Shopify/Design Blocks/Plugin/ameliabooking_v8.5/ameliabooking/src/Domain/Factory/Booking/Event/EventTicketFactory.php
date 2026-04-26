<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Event;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTicket;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Json;

/**
 * Class EventTicketFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Event
 */
class EventTicketFactory
{
    /**
     * @param $data
     *
     * @return EventTicket
     * @throws InvalidArgumentException
     */

    public static function create($data)
    {
        $eventTicket = new EventTicket();

        if (isset($data['id'])) {
            $eventTicket->setId(new Id($data['id']));
        }

        if (isset($data['eventId'])) {
            $eventTicket->setEventId(new Id($data['eventId']));
        }

        if (isset($data['name'])) {
            $eventTicket->setName(new Name($data['name']));
        }

        if (isset($data['enabled'])) {
            $eventTicket->setEnabled(new BooleanValueObject($data['enabled']));
        }

        if (isset($data['price'])) {
            $eventTicket->setPrice(new Price($data['price']));
        }

        if (isset($data['spots'])) {
            $eventTicket->setSpots(new IntegerValue($data['spots']));
        }

        if (isset($data['waitingListSpots'])) {
            $eventTicket->setWaitingListSpots(new IntegerValue($data['waitingListSpots']));
        }

        if (isset($data['dateRanges'])) {
            $eventTicket->setDateRanges(new Json($data['dateRanges']));
        }

        if (isset($data['sold'])) {
            $eventTicket->setSold(new IntegerValue($data['sold']));
        }

        if (!empty($data['translations'])) {
            $eventTicket->setTranslations(new Json($data['translations']));
        }

        return $eventTicket;
    }
}
