<?php

namespace AmeliaBooking\Application\Commands\Bookable\Package;

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
 * Class GetPackageDeleteEffectCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Package
 */
class GetPackageDeleteEffectCommandHandler extends CommandHandler
{
    /**
     * @param GetPackageDeleteEffectCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(GetPackageDeleteEffectCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::PACKAGES)) {
            throw new AccessDeniedException('You are not allowed to read packages');
        }

        $result = new CommandResult();

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->getContainer()->get('application.bookable.service');

        $appointmentsCount = $bookableAS->getAppointmentsCountForPackages([$command->getArg('id')]);

        $message = '';

        if ($appointmentsCount['futureAppointments'] > 0) {
            $appointmentString = $appointmentsCount['futureAppointments'] === 1 ? 'appointment' : 'appointments';

            $message = "This package has {$appointmentsCount['futureAppointments']} {$appointmentString} in the future.";
        } elseif ($appointmentsCount['pastAppointments'] > 0) {
            $appointmentString = $appointmentsCount['pastAppointments'] === 1 ? 'appointment' : 'appointments';

            $message = "This package has {$appointmentsCount['pastAppointments']} {$appointmentString} in the past.";
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved message.');
        $result->setData(
            [
                'valid'   => true,
                'message' => $message
            ]
        );

        return $result;
    }
}
