<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class BookingType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class BookingType
{
    public const APPOINTMENT = Entities::APPOINTMENT;
    public const EVENT       = Entities::EVENT;

    /**
     * @var string
     */
    private $bookingType;

    /**
     * BookingType constructor.
     *
     * @param string $bookingType
     */
    public function __construct($bookingType)
    {
        $this->bookingType = $bookingType;
    }

    /**
     * Return the notification type from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->bookingType;
    }
}
