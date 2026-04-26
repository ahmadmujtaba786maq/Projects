<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;

/**
 * Class ResourceEntitiesRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Service
 */
class ResourceEntitiesRepository extends AbstractRepository
{
    /**
     * @param Connection $connection
     * @param string     $table
     */
    public function __construct(
        Connection $connection,
        $table
    ) {
        parent::__construct($connection, $table);
    }

    /**
     * @param array $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $params = [
            ':resourceId' => $entity['resourceId'],
            ':entityId'   => $entity['entityId'],
            ':entityType' => $entity['entityType'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO 
                {$this->table} 
                (
                `resourceId`, 
                `entityId`,
                `entityType`
                ) VALUES (
                :resourceId,
                :entityId,
                :entityType
                )"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int     $resourceEntityId
     * @param array   $entity
     *
     * @throws QueryExecutionException
     */
    public function update($resourceEntityId, $entity)
    {
        $params = [
            ':resourceId' => $entity['resourceId'],
            ':entityId'   => $entity['entityId'],
            ':entityType' => $entity['entityType'],
            ':id'         => $resourceEntityId
        ];


        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `resourceId` = :resourceId,
                `entityId`   = :entityId,
                `entityType` = :entityType,
                WHERE
                id = :id"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param $id
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getByResourceId($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE resourceId = :resourceId"
            );

            $params = [
                ':resourceId' => $id
            ];

            $statement->execute($params);

            $entityRows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get entities in ' . __CLASS__, $e->getCode(), $e);
        }

        return $entityRows;
    }

    /**
     * @param int    $entityId
     * @param string $entityType
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteByEntityIdAndEntityType($entityId, $entityType)
    {
        $params = [
            ':entityId'   => $entityId,
            ':entityType' => $entityType,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE entityId = :entityId AND entityType = :entityType"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete entities in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int    $entityId
     * @param string $entityType
     * @param int    $resourceId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteByEntityIdAndEntityTypeAndResourceId($entityId, $entityType, $resourceId)
    {
        $params = [
            ':entityId'   => $entityId,
            ':entityType' => $entityType,
            ':resourceId' => $resourceId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE entityId = :entityId AND entityType = :entityType AND resourceId = :resourceId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete entities in ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
