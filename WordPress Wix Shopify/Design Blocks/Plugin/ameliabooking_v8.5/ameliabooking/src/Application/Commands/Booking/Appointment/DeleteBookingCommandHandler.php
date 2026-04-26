<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Reservation\AppointmentReservationService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use Slim\Exception\ContainerValueNotFoundException;
use Interop\Container\Exception\ContainerException;

/**
 * Class DeleteBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class DeleteBookingCommandHandler extends CommandHandler
{
    /**
     * @param DeleteBookingCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(DeleteBookingCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to delete appointment');
        }

        $result = new CommandResult();

        /** @var AppointmentReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

        try {
            $resultData = $reservationService->deleteBooking($command->getArg('id'));
        } catch (\Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete booking');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted booking');
        $result->setData($resultData);

        return $result;
    }
}
