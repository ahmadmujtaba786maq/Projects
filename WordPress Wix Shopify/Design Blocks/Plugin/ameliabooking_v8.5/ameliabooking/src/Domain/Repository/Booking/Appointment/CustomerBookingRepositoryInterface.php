<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface CustomerBookingRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\Booking\Appointment
 */
interface CustomerBookingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param int $id
     * @param int $status
     *
     * @return mixed
     */
    public function updateStatusById($id, $status);

    /**
     * @param int $id
     * @param int $status
     *
     * @return mixed
     */
    public function updateStatusByAppointmentId($id, $status);
}
