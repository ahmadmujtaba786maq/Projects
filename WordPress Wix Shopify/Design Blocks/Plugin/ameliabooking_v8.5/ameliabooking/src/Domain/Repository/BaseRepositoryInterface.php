<?php

namespace AmeliaBooking\Domain\Repository;

/**
 * Interface BaseRepositoryInterface
 *
 * @package AmeliaBooking\Domain\Repository
 */
interface BaseRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getById($id);

    /**
     * @return mixed
     */
    public function getAll();

    /**
     * @param $entity
     *
     * @return mixed
     */
    public function add($entity);

    /**
     * @param int $id
     * @param     $entity
     *
     * @return mixed
     */
    public function update($id, $entity);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function delete($id);

    /**
     * @return bool
     */
    public function beginTransaction();

    /**
     * @return bool
     */
    public function commit();

    /**
     * @return bool
     */
    public function rollback();
}
