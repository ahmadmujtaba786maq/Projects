<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use Slim\Exception\ContainerValueNotFoundException;
use Interop\Container\Exception\ContainerException;

/**
 * Class DeleteAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class DeleteAppointmentCommandHandler extends CommandHandler
{
    /**
     * @param DeleteAppointmentCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handle(DeleteAppointmentCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to delete appointment');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var AppointmentApplicationService $appointmentApplicationService */
        $appointmentApplicationService = $this->container->get('application.booking.appointment.service');

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getById($command->getArg('id'));

        $appointmentRepository->beginTransaction();

        do_action('amelia_before_appointment_deleted', $appointment ? $appointment->toArray() : null);

        if (!$appointmentApplicationService->delete($appointment)) {
            $appointmentRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete appointment');

            return $result;
        }

        // Set status to rejected, to send the notification that appointment is rejected
        $appointment->setStatus(new BookingStatus(BookingStatus::REJECTED));

        $bookingsWithChangedStatus = [];

        /** @var CustomerBooking $customerBooking */
        foreach ($appointment->getBookings()->getItems() as $customerBooking) {
            $bookingStatus = $customerBooking->getStatus()->getValue();

            if ($bookingStatus === BookingStatus::PENDING || $bookingStatus === BookingStatus::APPROVED) {
                $customerBooking->setChangedStatus(new BooleanValueObject(true));
                $bookingsWithChangedStatus[] = $customerBooking->toArray();
            }

            $customerBooking->setStatus(new BookingStatus(BookingStatus::REJECTED));
        }

        $appointmentApplicationService->manageDeletionParentRecurringAppointment($appointment->getId()->getValue());

        $appointmentRepository->commit();

        $customFieldService->deleteUploadedFilesForDeletedBookings(
            new Collection(),
            $appointment->getBookings()
        );

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted appointment');
        $result->setData(
            [
            Entities::APPOINTMENT       => $appointment->toArray(),
            'bookingsWithChangedStatus' => $bookingsWithChangedStatus
            ]
        );

        do_action('amelia_after_appointment_deleted', $appointment->toArray());

        return $result;
    }
}
