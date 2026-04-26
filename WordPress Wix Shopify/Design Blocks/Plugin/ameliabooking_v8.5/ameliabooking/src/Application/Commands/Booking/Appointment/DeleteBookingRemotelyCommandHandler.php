<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Reservation\AbstractReservationService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use Slim\Exception\ContainerValueNotFoundException;
use Interop\Container\Exception\ContainerException;

/**
 * Class DeleteBookingRemotelyCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class DeleteBookingRemotelyCommandHandler extends CommandHandler
{
    /**
     * @param DeleteBookingRemotelyCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(DeleteBookingRemotelyCommand $command)
    {
        $result = new CommandResult();

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        $bookingId = $command->getArg('id');

        $token = $command->getField('token');

        if (!$token) {
            throw new AccessDeniedException('No token sent');
        }

        /** @var AbstractReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        try {
            $reservationService->deleteBooking($bookingId, $token);
        } catch (\Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted booking');

        return $result;
    }
}
