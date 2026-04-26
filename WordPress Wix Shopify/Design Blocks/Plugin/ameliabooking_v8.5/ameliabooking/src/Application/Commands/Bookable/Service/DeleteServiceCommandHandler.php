<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class DeleteServiceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Service
 */
class DeleteServiceCommandHandler extends CommandHandler
{
    /**
     * @param DeleteServiceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(DeleteServiceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to delete services.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var BookableApplicationService $bookableApplicationService */
        $bookableApplicationService = $this->getContainer()->get('application.bookable.service');

        $appointmentsCount = $bookableApplicationService->getAppointmentsCountForServices([$command->getArg('id')]);

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var Service $service */
        $service = $serviceRepository->getByCriteria(
            ['services' => [$command->getArg('id')]]
        )->getItem($command->getArg('id'));

        if ($appointmentsCount['futureAppointments']) {
            $result->setResult(CommandResult::RESULT_CONFLICT);
            $result->setMessage('Could not delete service.');
            $result->setData([]);

            return $result;
        }

        $serviceRepository->beginTransaction();

        do_action('amelia_before_service_deleted', $service->toArray());

        if (!$bookableApplicationService->deleteService($service)) {
            $serviceRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete service.');

            return $result;
        }

        $serviceRepository->commit();

        do_action('amelia_after_service_deleted', $service->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted service.');
        $result->setData([]);

        return $result;
    }
}
