<?php

namespace AmeliaBooking\Application\Services\Location;

use AmeliaBooking\Domain\Collection\Collection;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BasicLocationApplicationService
 *
 * @package AmeliaBooking\Application\Services\Location
 */
class BasicLocationApplicationService extends AbstractLocationApplicationService
{
    /**
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     */
    public function getAllOrderedByName()
    {
        return new Collection();
    }

    /**
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     */
    public function getAllIndexedById()
    {
        return new Collection();
    }
}
