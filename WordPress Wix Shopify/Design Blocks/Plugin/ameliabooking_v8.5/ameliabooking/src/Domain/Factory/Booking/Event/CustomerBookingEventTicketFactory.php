<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Event;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventTicket;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;

/**
 * Class CustomerBookingEventTicketFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Event
 */
class CustomerBookingEventTicketFactory
{
    /**
     * @param $data
     *
     * @return CustomerBookingEventTicket
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $customerBookingEventTicket = new CustomerBookingEventTicket();

        if (!empty($data['id'])) {
            $customerBookingEventTicket->setId(new Id($data['id']));
        }

        if (!empty($data['eventTicketId'])) {
            $customerBookingEventTicket->setEventTicketId(new Id($data['eventTicketId']));
        }

        if (!empty($data['customerBookingId'])) {
            $customerBookingEventTicket->setCustomerBookingId(new Id($data['customerBookingId']));
        }

        if (!empty($data['persons'])) {
            $customerBookingEventTicket->setPersons(new IntegerValue($data['persons']));
        }

        if (isset($data['price'])) {
            $customerBookingEventTicket->setPrice(new Price((float)$data['price']));
        }

        return $customerBookingEventTicket;
    }
}
