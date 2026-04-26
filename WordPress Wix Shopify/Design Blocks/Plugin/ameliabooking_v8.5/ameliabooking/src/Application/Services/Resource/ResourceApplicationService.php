<?php

namespace AmeliaBooking\Application\Services\Resource;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ResourceRepository;

/**
 * Class ResourceApplicationService
 *
 * @package AmeliaBooking\Application\Services\Resource
 */
class ResourceApplicationService extends AbstractResourceApplicationService
{
    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAll($criteria)
    {
        /** @var ResourceRepository $resourceRepository */
        $resourceRepository = $this->container->get('domain.bookable.resource.repository');

        /** @var Collection $resources */
        $resources = $resourceRepository->getByCriteria($criteria);

        return $resources;
    }
}
