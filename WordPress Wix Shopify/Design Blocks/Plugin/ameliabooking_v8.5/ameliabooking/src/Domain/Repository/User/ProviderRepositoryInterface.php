<?php

namespace AmeliaBooking\Domain\Repository\User;

use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface ProviderRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\User
 */
interface ProviderRepositoryInterface extends BaseRepositoryInterface
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
