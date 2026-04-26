<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Services\Zoom;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class StarterZoomApplicationService
 *
 * @package AmeliaBooking\Application\Services\Zoom
 */
class StarterZoomApplicationService extends AbstractZoomApplicationService
{
    /**
     * @param Appointment $reservation
     * @param string      $commandSlug
     *
     * @return void
     */
    public function handleAppointmentMeeting($reservation, $commandSlug)
    {
    }

    /**
     * @param Event      $reservation
     * @param Collection $periods
     * @param string     $commandSlug
     *
     * @return void
     */
    public function handleEventMeeting($reservation, $periods, $commandSlug, $newZoomUser = null)
    {
    }

    /**
     * @return array
     */
    public function getUsers()
    {
        return [];
    }

    /**
     * @param Appointment|EventPeriod $reservation
     * @param AbstractRepository      $repository
     *
     * @return void
     */
    public function removeMeeting($reservation, $repository)
    {
    }
}
