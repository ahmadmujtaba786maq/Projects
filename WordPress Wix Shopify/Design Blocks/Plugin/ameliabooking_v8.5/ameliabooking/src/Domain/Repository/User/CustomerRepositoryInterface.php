<?php

namespace AmeliaBooking\Domain\Repository\User;

use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface CustomerRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\User
 */
interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param     $criteria
     * @param int $itemsPerPage
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
}
