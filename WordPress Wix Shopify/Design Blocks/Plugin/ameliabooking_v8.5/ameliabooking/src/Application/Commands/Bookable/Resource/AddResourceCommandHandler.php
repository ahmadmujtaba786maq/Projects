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
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceEntitiesRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddResourceCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Resource
 */
class AddResourceCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'name',
        'quantity',
    ];

    /**
     * @param AddResourceCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(AddResourceCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::RESOURCES)) {
            throw new AccessDeniedException('You are not allowed to add resources.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $resourceData = $command->getFields();

        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForResource($resourceData);

        /** @var ResourceRepository $resourceRepository */
        $resourceRepository = $this->container->get('domain.bookable.resource.repository');

        $resourceData = apply_filters('amelia_before_resource_added_filter', $resourceData);

        do_action('amelia_before_resource_added', $resourceData);

        /** @var Resource $resource */
        $resource = ResourceFactory::create($resourceData);

        if (!($resource instanceof Resource)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create resource.');

            return $result;
        }

        $resourceRepository->beginTransaction();

        if (!($resourceId = $resourceRepository->add($resource))) {
            $resourceRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not create resource.');

            return $result;
        }

        $resourceRepository->commit();

        $resource->setId(new Id($resourceId));

        /** @var ResourceEntitiesRepository $resourceEntitiesRepository */
        $resourceEntitiesRepository = $this->container->get('domain.bookable.resourceEntities.repository');

        foreach ($resourceData['entities'] as $entity) {
            $entity['resourceId'] = $resourceId;
            $resourceEntitiesRepository->add($entity);
        }

        do_action('amelia_after_resource_added', $resource->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new resource.');
        $result->setData(
            [
                Entities::RESOURCE => $resource->toArray(),
            ]
        );

        return $result;
    }
}
