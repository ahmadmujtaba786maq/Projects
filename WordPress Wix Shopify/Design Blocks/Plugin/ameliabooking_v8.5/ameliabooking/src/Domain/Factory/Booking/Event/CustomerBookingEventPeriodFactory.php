<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking\Event;

use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventPeriod;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class CustomerBookingEventPeriodFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking\Event
 */
class CustomerBookingEventPeriodFactory
{
    /**
     * @param $data
     *
     * @return CustomerBookingEventPeriod
     */
    public static function create($data)
    {
        $customerBookingEventPeriod = new CustomerBookingEventPeriod();

        if (!empty($data['id'])) {
            $customerBookingEventPeriod->setId(new Id($data['id']));
        }

        if (!empty($data['eventPeriodId'])) {
            $customerBookingEventPeriod->setEventPeriodId(new Id($data['eventPeriodId']));
        }

        if (!empty($data['customerBookingId'])) {
            $customerBookingEventPeriod->setCustomerBookingId(new Id($data['customerBookingId']));
        }

        return $customerBookingEventPeriod;
    }
}
