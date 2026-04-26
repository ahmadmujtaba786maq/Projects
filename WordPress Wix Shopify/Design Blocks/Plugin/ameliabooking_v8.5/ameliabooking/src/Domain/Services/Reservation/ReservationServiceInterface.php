<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingsLimitReachedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\EventBookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Common\Exceptions\PackageBookingUnavailableException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\AbstractCustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Interface ReservationServiceInterface
 *
 * @package AmeliaBooking\Domain\Services\Reservation
 */
interface ReservationServiceInterface
{
    /**
     * @param CustomerBooking $booking
     * @param string          $requestedStatus
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws BookingCancellationException
     */
    public function updateStatus($booking, $requestedStatus);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param int             $bookingId
     * @param int             $packageCustomerId
     * @param array           $paymentData
     * @param float           $amount
     * @param DateTime        $dateTime
     * @param string          $entityType
     *
     * @return Payment
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function addPayment($bookingId, $packageCustomerId, $paymentData, $amount, $dateTime, $entityType);

    /**
     * @param array       $data
     * @param Reservation $reservation
     * @param bool        $save
     *
     * @return CommandResult
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function processRequest($data, $reservation, $save);

    /**
     * @param CommandResult $result
     *
     * @return void
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function runPostBookingActions($result);

    /**
     * @param array       $appointmentData
     * @param Reservation $reservation
     * @param bool        $save
     *
     * @return void
     *
     * @throws BookingUnavailableException
     * @throws BookingsLimitReachedException
     * @throws CustomerBookedException
     * @throws PackageBookingUnavailableException
     * @throws EventBookingUnavailableException
     * @throws CouponExpiredException
     * @throws CouponInvalidException
     * @throws CouponUnknownException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function book($appointmentData, $reservation, $save);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult $result
     * @param array         $appointmentData
     * @param Reservation   $reservation
     * @param bool          $save
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function processBooking($result, $appointmentData, $reservation, $save);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult $result
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     * @param bool          $isCart
     *
     * @throws ContainerException
     */
    public function finalize($result, $reservation, $bookingType, $isCart);

    /**
     * @param AbstractCustomerBooking $booking
     * @param AbstractBookable        $bookable
     * @oaram boolean                 $invoice
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getPaymentAmount($booking, $bookable, $invoice = false);

    /**
     * @param Appointment|Event  $reservation
     * @param CustomerBooking  $booking
     * @param AbstractBookable $bookable
     *
     * @return array
     */
    public function getBookingPeriods($reservation, $booking, $bookable);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param array $data
     *
     * @return AbstractBookable
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function getBookableEntity($data);

    /**
     * @param Service|Event $bookable
     *
     * @return boolean
     */
    public function isAggregatedPrice($bookable);

    /**
     * @param BooleanValueObject $bookableAggregatedPrice
     * @param BooleanValueObject $extraAggregatedPrice
     *
     * @return boolean
     */
    public function isExtraAggregatedPrice($extraAggregatedPrice, $bookableAggregatedPrice);

    /**
     * @param Reservation $reservation
     * @param string      $paymentGateway
     * @param array       $requestData
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getWooCommerceData($reservation, $paymentGateway, $requestData);

    /**
     * @param array $reservation
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getWooCommerceDataFromArray($reservation, $index);

    /**
     * @param int $id
     *
     * @return Appointment|Event
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function getReservationByBookingId($id);

    /**
     * @param int $bookingId
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getBookingResultByBookingId($bookingId);

    /**
     * @param DateTime $bookingStart
     * @param int       $minimumCancelTime
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws BookingCancellationException
     */
    public function inspectMinimumCancellationTime($bookingStart, $minimumCancelTime);

    /**
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     *
     * @return array
     */
    public function getResultData($reservation, $bookingType);

    /**
     * @param bool $couponValidation
     * @param bool $customFieldsValidation
     * @param bool $availabilityValidation
     *
     * @return Reservation
     */
    public function getNew($couponValidation, $customFieldsValidation, $availabilityValidation);

    /**
     * @param Payment $payment
     * @param boolean $fromLink
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function getReservationByPayment($payment, $fromLink = false);

    /**
     * @param string $type
     * @param string $orderStatus
     * @param string $statusTarget
     * @param bool   $isUpdate
     *
     * @return mixed
     */
    public function getWcStatus($type, $orderStatus, $statusTarget, $isUpdate);

    /**
     * @param Reservation  $reservation
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function getReservationPaymentAmount($reservation);

    /**
     * @param Reservation $reservation
     * @param bool        $usePayment
     *
     * @return array
     */
    public function getProvidersPaymentAmount($reservation, $usePayment = true);

    /**
     * @param array $data
     *
     * @return void
     * @throws QueryExecutionException
     */
    public function manageTaxes(&$data);
}
