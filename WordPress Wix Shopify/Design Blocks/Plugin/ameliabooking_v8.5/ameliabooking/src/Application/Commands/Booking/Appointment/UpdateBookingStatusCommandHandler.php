<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use Interop\Container\Exception\ContainerException;

/**
 * Class UpdateBookingStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class UpdateBookingStatusCommandHandler extends CommandHandler
{
    /**
     * @param UpdateBookingStatusCommand $command
     *
     * @return CommandResult
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handle(UpdateBookingStatusCommand $command)
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

        do_action('amelia_before_booking_' . $command->getField('status'), $booking ? $booking->toArray() : null);

        try {
            $bookingData = $reservationService->updateStatus($booking, $command->getField('status'));
        } catch (BookingCancellationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('You are not allowed to update booking status');
            $result->setData(
                [
                    'updateBookingUnavailable' => true
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
                    'status'  => $command->getField('status'),
                    'message' =>
                        BackendStrings::getAppointmentStrings()['booking_status_changed']
                ]
            )
        );

        do_action('amelia_after_booking_' . $command->getField('status'), $bookingData);

        return $result;
    }
}
