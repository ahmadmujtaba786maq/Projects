<?php

namespace AmeliaBooking\Application\Commands\Location;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Location\LocationApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Repository\Location\LocationRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class DeleteLocationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Location
 */
class DeleteLocationCommandHandler extends CommandHandler
{
    /**
     * @param DeleteLocationCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws QueryExecutionException
     */
    public function handle(DeleteLocationCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::LOCATIONS)) {
            throw new AccessDeniedException('You are not allowed to delete location');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var LocationRepositoryInterface $locationRepository */
        $locationRepository = $this->getContainer()->get('domain.locations.repository');

        /** @var LocationApplicationService $locationApplicationService */
        $locationApplicationService = $this->container->get('application.location.service');

        /** @var Location $location */
        $location = $locationRepository->getById($command->getArg('id'));

        $locationRepository->beginTransaction();

        do_action('amelia_before_location_deleted', $location->toArray());

        if (!$locationApplicationService->delete($location)) {
            $locationRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete location.');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted location.');
        $result->setData(
            [
                Entities::LOCATION => $location->toArray()
            ]
        );

        $locationRepository->commit();

        do_action('amelia_after_location_deleted', $location->toArray());

        return $result;
    }
}
