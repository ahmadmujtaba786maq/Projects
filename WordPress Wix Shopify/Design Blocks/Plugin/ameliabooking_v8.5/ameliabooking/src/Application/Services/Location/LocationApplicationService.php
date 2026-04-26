<?php

namespace AmeliaBooking\Application\Services\Location;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class LocationApplicationService
 *
 * @package AmeliaBooking\Application\Services\Location
 */
class LocationApplicationService extends AbstractLocationApplicationService
{
    /**
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllOrderedByName()
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        return $locationRepository->getAllOrderedByName();
    }

    /**
     * @return Collection
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAllIndexedById()
    {
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        return $locationRepository->getAllIndexedById();
    }
}
