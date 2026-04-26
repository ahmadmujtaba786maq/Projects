<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class DeleteUserCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User
 */
class DeleteUserCommandHandler extends CommandHandler
{
    /**
     * @param DeleteUserCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function handle(DeleteUserCommand $command)
    {
        if (
            !$command->getPermissionService()->currentUserCanDelete(Entities::EMPLOYEES) &&
            !$command->getPermissionService()->currentUserCanDelete(Entities::CUSTOMERS)
        ) {
            throw new AccessDeniedException('You are not allowed to read user');
        }

        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        $appointmentsCount = $userAS->getAppointmentsCountForUser($command->getArg('id'));

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        if ($appointmentsCount['futureAppointments']) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Could not delete user.');
            $result->setData([]);

            return $result;
        }

        /** @var AbstractUser $user */
        $user = $userRepository->getById($command->getArg('id'));

        $userRepository->beginTransaction();

        do_action('amelia_before_user_deleted', $user ? $user->toArray() : null);

        if ($user->getType() === AbstractUser::USER_ROLE_PROVIDER) {
            /** @var ProviderApplicationService $providerApplicationService */
            $providerApplicationService = $this->getContainer()->get('application.user.provider.service');

            /** @var Provider $provider */
            $provider = $providerApplicationService->getProviderWithServicesAndSchedule($user->getId()->getValue());

            if (!$providerApplicationService->delete($provider)) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not delete user.');
                $userRepository->rollback();

                return $result;
            }
        }

        if (
            $user->getType() === AbstractUser::USER_ROLE_CUSTOMER ||
            $user->getType() === AbstractUser::USER_ROLE_ADMIN
        ) {
            /** @var CustomerApplicationService $customerApplicationService */
            $customerApplicationService = $this->getContainer()->get('application.user.customer.service');

            if (!$customerApplicationService->delete($user)) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not delete user.');
                $userRepository->rollback();

                return $result;
            }
        }

        /** @var Collection $emptyAppointments */
        $emptyAppointments = $appointmentRepository->getAppointmentsWithoutBookings();

        /** @var Appointment $appointment */
        foreach ($emptyAppointments->getItems() as $appointment) {
            if (!$appointmentRepository->delete($appointment->getId()->getValue())) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not delete user.');
                $userRepository->rollback();

                return $result;
            }
        }

        $userRepository->commit();

        do_action('amelia_after_user_deleted', $user->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted user.');
        $result->setData([]);

        return $result;
    }
}
