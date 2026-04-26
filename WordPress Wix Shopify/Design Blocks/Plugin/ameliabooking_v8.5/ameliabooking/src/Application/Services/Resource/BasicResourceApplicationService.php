<?php

namespace AmeliaBooking\Application\Services\Resource;

use AmeliaBooking\Domain\Collection\Collection;

/**
 * Class BasicResourceApplicationService
 *
 * @package AmeliaBooking\Application\Services\Resource
 */
class BasicResourceApplicationService extends AbstractResourceApplicationService
{
    /**
     * @param array $criteria
     *
     * @return Collection
     */
    public function getAll($criteria)
    {
        return new Collection();
    }
}
