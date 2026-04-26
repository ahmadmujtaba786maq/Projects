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
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class AbstractZoomApplicationService
 *
 * @package AmeliaBooking\Application\Services\Zoom
 */
abstract class AbstractZoomApplicationService
{
    /** @var Container $container */
    protected $container;

    /**
     * AbstractZoomApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Appointment $reservation
     * @param string      $commandSlug
     *
     * @return void
     */
    abstract public function handleAppointmentMeeting($reservation, $commandSlug);

    /**
     * @param Event      $reservation
     * @param Collection $periods
     * @param string     $commandSlug
     *
     * @return void
     */
    abstract public function handleEventMeeting($reservation, $periods, $commandSlug, $newZoomUser = null);

    /**
     * @return array
     */
    abstract public function getUsers();

    /**
     * @param Appointment|EventPeriod $reservation
     * @param AbstractRepository      $repository
     *
     * @return void
     */
    abstract public function removeMeeting($reservation, $repository);
}
