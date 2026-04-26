<?php

namespace AmeliaBooking\Domain\Repository\Location;

use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface LocationRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\Location
 */
interface LocationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param $criteria
     * @param $itemsPerPage
     *
     * @return mixed
     */
    public function getFiltered($criteria, $itemsPerPage);

    /**
     * @param $criteria
     *
     * @return mixed
     */
    public function getCount($criteria);

    /**
     * @param $locationId
     *
     * @return mixed
     */
    public function getServicesById($locationId);
}
