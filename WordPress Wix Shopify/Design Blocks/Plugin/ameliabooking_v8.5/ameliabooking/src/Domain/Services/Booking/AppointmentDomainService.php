<?php

namespace AmeliaBooking\Domain\Services\Booking;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;

/**
 * Class AppointmentDomainService
 *
 * @package AmeliaBooking\Domain\Services\Booking
 */
class AppointmentDomainService
{
    /**
     * Returns an array with bookings statuses count for passed appointment
     *
     * @param Appointment $appointment
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getBookingsStatusesCount($appointment)
    {
        $approvedBookings = 0;
        $pendingBookings  = 0;
        $canceledBookings = 0;
        $rejectedBookings = 0;
        $noShowBookings   = 0;
        $waitingBookings  = 0;

        foreach ((array)$appointment->getBookings()->keys() as $customerBookingKey) {
            /** @var CustomerBooking $booking */
            $booking = $appointment->getBookings()->getItem($customerBookingKey);

            switch ($booking->getStatus()->getValue()) {
                case BookingStatus::PENDING:
                    $pendingBookings += $booking->getPersons()->getValue();
                    break;
                case BookingStatus::CANCELED:
                    $canceledBookings += $booking->getPersons()->getValue();
                    break;
                case BookingStatus::REJECTED:
                    $rejectedBookings += $booking->getPersons()->getValue();
                    break;
                case BookingStatus::NO_SHOW:
                    $noShowBookings += $booking->getPersons()->getValue();
                    break;
                case BookingStatus::WAITING:
                    $waitingBookings += $booking->getPersons()->getValue();
                    break;
                default:
                    $approvedBookings += $booking->getPersons()->getValue();
                    break;
            }
        }

        return [
            'approvedBookings' => $approvedBookings,
            'pendingBookings'  => $pendingBookings,
            'canceledBookings' => $canceledBookings,
            'rejectedBookings' => $rejectedBookings,
            'noShowBookings'   => $noShowBookings,
            'waitingBookings'  => $waitingBookings
        ];
    }

    /**
     * @param Service $service
     * @param array   $bookingsCount
     *
     * @return string
     */
    public function getAppointmentStatusWhenEditAppointment($service, $bookingsCount)
    {
        $totalBookings = array_sum($bookingsCount);

        if ($bookingsCount['canceledBookings'] === $totalBookings) {
            return BookingStatus::CANCELED;
        }

        if ($bookingsCount['noShowBookings'] === $totalBookings) {
            return BookingStatus::NO_SHOW;
        }

        if ($bookingsCount['waitingBookings'] === $totalBookings) {
            return BookingStatus::WAITING;
        }

        if ($bookingsCount['rejectedBookings'] === $totalBookings) {
            return BookingStatus::REJECTED;
        }

        if ($bookingsCount['approvedBookings'] === 0 && $bookingsCount['pendingBookings'] === 0) {
            return BookingStatus::CANCELED;
        }

        return $bookingsCount['approvedBookings'] >= $service->getMinCapacity()->getValue() ?
            BookingStatus::APPROVED : BookingStatus::PENDING;
    }

    /**
     * When booking status is changed, find out appointment status.
     *
     * If there is no any more 'approved' and 'pending' bookings, set appointment status to 'canceled' or 'rejected'.
     *
     * If appointment status is 'approved' or 'pending' and minimum capacity condition is not satisfied,
     * set appointment status to 'pending'.
     *
     * @param Service $service
     * @param array   $bookingsCount
     * @param string  $requestedStatus
     *
     * @return string
     */
    public function getAppointmentStatusWhenChangingBookingStatus($service, $bookingsCount, $requestedStatus)
    {
        if ($bookingsCount['approvedBookings'] === 0 && $bookingsCount['pendingBookings'] === 0) {
            return $requestedStatus;
        }

        return $bookingsCount['approvedBookings'] >= $service->getMinCapacity()->getValue() ?
            BookingStatus::APPROVED : BookingStatus::PENDING;
    }

    /**
     * sort and merge appointments by date-time
     *
     * @param Collection $appointments
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function getSortedAndMergedAppointments($appointments)
    {
        $timeStampsData = [];

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $index => $appointment) {
            $timeStampStart = $appointment->getBookingStart()->getValue()->getTimestamp();

            $timeStampEnd = $appointment->getBookingEnd()->getValue()->getTimestamp();

            if (!isset($timeStampsData[$timeStampStart])) {
                $timeStampsData[$timeStampStart] = [$timeStampEnd, $index];
            } else {
                /** @var Appointment $passedAppointment */
                $passedAppointment = $appointments->getItem($timeStampsData[$timeStampStart][1]);

                if ($appointment->getBookingEnd()->getValue() > $passedAppointment->getBookingEnd()->getValue()) {
                    $timeStampsData[$timeStampStart] = [$timeStampEnd, $index];
                }
            }
        }

        ksort($timeStampsData);

        $mergedTimeStampsData = [];

        $previousInterval = null;

        foreach ($timeStampsData as $start => $currentInterval) {
            if ($previousInterval !== null && $start <= $previousInterval[1]) {
                if ($currentInterval[0] > $previousInterval[1]) {
                    $mergedTimeStampsData[$previousInterval[0]][0] = $currentInterval[0];
                }
            } else {
                $mergedTimeStampsData[$start] = $currentInterval;

                $previousInterval = [
                    $start,
                    $currentInterval[0],
                    $currentInterval[1]
                ];
            }
        }

        /** @var Collection $sortedAndMergedAppointments */
        $sortedAndMergedAppointments = new Collection();

        foreach ($mergedTimeStampsData as $start => $interval) {
            /** @var Appointment $appointment */
            $appointment = $appointments->getItem($interval[1]);

            $appointment->setBookingStart(
                new DateTimeValue(DateTimeService::getNowDateTimeObject()->setTimestamp($start))
            );

            $appointment->setBookingEnd(
                new DateTimeValue(DateTimeService::getNowDateTimeObject()->setTimestamp($interval[0]))
            );

            $sortedAndMergedAppointments->addItem($appointment);
        }

        return $sortedAndMergedAppointments;
    }
}
