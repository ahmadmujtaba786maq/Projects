<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use Interop\Container\Exception\ContainerException;

/**
 * Class CancelBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class CancelBookingCommandHandler extends CommandHandler
{
    /**
     * @param CancelBookingCommand $command
     *
     * @return CommandResult
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handle(CancelBookingCommand $command)
    {
        $result = new CommandResult();

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        try {
            /** @var AbstractUser $user */
            $user = $command->getUserApplicationService()->authorization(
                $command->getPage() === 'cabinet' ? $command->getToken() : null,
                $command->getCabinetType()
            );
        } catch (AuthorizationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData(
                [
                'reauthorize' => true
                ]
            );

            return $result;
        }

        /** @var CustomerBooking $booking */
        $booking = $bookingRepository->getById((int)$command->getArg('id'));

        $token = $bookingRepository->getToken((int)$command->getArg('id'));

        if (!empty($token['token'])) {
            $booking->setToken(new Token($token['token']));
        }

        if (!$command->getUserApplicationService()->isCustomerBooking($booking, $user, null)) {
            throw new AccessDeniedException('You are not allowed to update booking status');
        }

        do_action('amelia_before_booking_canceled', $booking ? $booking->toArray() : null);

        try {
            $bookingData = $reservationService->updateStatus($booking, BookingStatus::CANCELED);
        } catch (BookingCancellationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('You are not allowed to update booking status');
            $result->setData(
                [
                'cancelBookingUnavailable' => true
                ]
            );

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated booking status');
        $result->setData(
            array_merge(
                $bookingData,
                [
                'type'    => $type,
                'status'  => BookingStatus::CANCELED,
                'message' =>
                    BackendStrings::getAppointmentStrings()['appointment_status_changed'] .
                    strtolower(BackendStrings::getCommonStrings()[BookingStatus::CANCELED])
                ]
            )
        );

        do_action('amelia_after_booking_canceled', $bookingData);

        return $result;
    }
}
