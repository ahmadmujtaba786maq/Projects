<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Bookable\Resource;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Resource;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ResourceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceEntitiesRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateResourceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Resource
 */
class UpdateResourceCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'name',
        'quantity',
    ];

    /**
     * @param UpdateResourceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(UpdateResourceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::RESOURCES)) {
            throw new AccessDeniedException('You are not allowed to update resource.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $resourceData = $command->getFields();

        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForResource($resourceData);

        $resourceData = apply_filters('amelia_before_resource_updated_filter', $resourceData);

        do_action('amelia_before_resource_updated', $resourceData);

        /** @var Resource $resource */
        $resource = ResourceFactory::create($resourceData);

        if (!($resource instanceof Resource)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to update resource.');

            return $result;
        }

        /** @var ResourceRepository $resourceRepository */
        $resourceRepository = $this->container->get('domain.bookable.resource.repository');

        $resourceRepository->beginTransaction();

        $resourceId = $command->getArg('id');

        $resourceRepository->update($resourceId, $resource);

        $resourceRepository->commit();

        /** @var ResourceEntitiesRepository $resourceEntitiesRepository */
        $resourceEntitiesRepository = $this->container->get('domain.bookable.resourceEntities.repository');

        $currentEntitiesList = array_map(
            function ($v) {
                return ['entityId' => $v['entityId'], 'entityType' => $v['entityType']];
            },
            $resourceEntitiesRepository->getByResourceId($resourceId)
        );

        foreach ($currentEntitiesList as $entity) {
            if (!in_array($entity, $resource->getEntities())) {
                $resourceEntitiesRepository->deleteByEntityIdAndEntityTypeAndResourceId(
                    $entity['entityId'],
                    $entity['entityType'],
                    $resourceId
                );
            }
        }
        foreach ($resource->getEntities() as $entity) {
            if (!in_array($entity, $currentEntitiesList)) {
                $entity['resourceId'] = $resourceId;
                $resourceEntitiesRepository->add($entity);
            }
        }

        do_action('amelia_after_resource_updated', $resource->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated resource.');
        $result->setData(
            [
                Entities::RESOURCE => $resource->toArray(),
            ]
        );

        return $result;
    }
}
