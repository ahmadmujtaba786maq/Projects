<?php

namespace AmeliaBooking\Domain\Repository\User;

use AmeliaBooking\Domain\Repository\BaseRepositoryInterface;

/**
 * Interface UserRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository\User
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return mixed
     */
    public function findByExternalId($id);


    /**
     * @param $type
     *
     * @return mixed
     */
    public function getAllByType($type);
}
