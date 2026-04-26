<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Booking;

use AmeliaBooking\Domain\Entity\Booking\SlotsEntities;

/**
 * Class SlotsEntitiesFactory
 *
 * @package AmeliaBooking\Domain\Factory\Booking
 */
class SlotsEntitiesFactory
{
    /**
     * @return SlotsEntities
     */
    public static function create()
    {
        return new SlotsEntities();
    }
}
