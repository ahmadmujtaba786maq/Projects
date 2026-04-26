<?php

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetServiceDeleteEffectCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class GetServiceDeleteEffectCommandHandler extends CommandHandler
{
    /**
     * @param GetServiceDeleteEffectCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(GetServiceDeleteEffectCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to read services');
        }

        $result = new CommandResult();

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->getContainer()->get('application.bookable.service');

        $appointmentsCount = $bookableAS->getAppointmentsCountForServices([$command->getArg('id')]);

        $message = '';

        if ($appointmentsCount['futureAppointments'] > 0) {
            $appointmentString = $appointmentsCount['futureAppointments'] === 1 ? 'appointment' : 'appointments';

            $message = "Could not delete service. 
            This service has {$appointmentsCount['futureAppointments']} {$appointmentString} in the future.";
        } elseif ($appointmentsCount['packageAppointments']) {
            $message = "This service is available for booking in purchased package.
            Are you sure you want to delete this service?";
        } elseif ($appointmentsCount['pastAppointments'] > 0) {
            $appointmentString = $appointmentsCount['pastAppointments'] === 1 ? 'appointment' : 'appointments';

            $message = "This service has {$appointmentsCount['pastAppointments']} {$appointmentString} in the past.";
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved message.');
        $result->setData(
            [
                'valid'   => $appointmentsCount['futureAppointments'] ? false : true,
                'message' => $message
            ]
        );

        return $result;
    }
}
