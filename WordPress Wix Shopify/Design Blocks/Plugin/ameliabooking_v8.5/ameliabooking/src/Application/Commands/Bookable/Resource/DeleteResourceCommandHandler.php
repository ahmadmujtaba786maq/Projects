<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Resource;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceEntitiesRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class DeleteResourceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Resource
 */
class DeleteResourceCommandHandler extends CommandHandler
{
    /**
     * @param DeleteResourceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(DeleteResourceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::RESOURCES)) {
            throw new AccessDeniedException('You are not allowed to delete resources.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var ResourceRepository $resourceRepository */
        $resourceRepository = $this->container->get('domain.bookable.resource.repository');

        /** @var ResourceEntitiesRepository $resourceEntitiesRepository */
        $resourceEntitiesRepository = $this->container->get('domain.bookable.resourceEntities.repository');

        $resourceId = $command->getArg('id');

        $resourceRepository->beginTransaction();

        do_action('amelia_before_resource_deleted', $resourceId);

        if (
            !$resourceEntitiesRepository->deleteByEntityId($resourceId, 'resourceId') ||
            !$resourceRepository->delete($resourceId)
        ) {
            $resourceRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete resource.');

            return $result;
        }

        $resourceRepository->commit();

        do_action('amelia_after_resource_deleted', $resourceId);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted resource.');
        $result->setData([]);

        return $result;
    }
}
