<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Interface AppointmentRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\Booking\Appointment
 */
interface AppointmentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param int $id
     * @param int $status
     *
     * @return mixed
     */
    public function updateStatusById($id, $status);

    /**
     * @return array
     */
    public function getCurrentAppointments();

    /**
     * @param Collection $collection
     * @param array      $providerIds
     * @param string     $startDateTime
     * @param string     $endDateTime
     * @return void
     * @throws QueryExecutionException
     */
    public function getFutureAppointments($collection, $providerIds, $startDateTime, $endDateTime);

    /**
     * @param array $criteria
     *
     * @return mixed
     */
    public function getFiltered($criteria);
}
