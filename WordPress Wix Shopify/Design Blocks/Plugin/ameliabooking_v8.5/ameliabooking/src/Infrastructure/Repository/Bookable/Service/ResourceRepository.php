<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Resource;
use AmeliaBooking\Domain\Factory\Bookable\Service\ResourceFactory;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ResourcesToEntitiesTable;

/**
 * Class ResourceRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Service
 */
class ResourceRepository extends AbstractRepository
{
    public const FACTORY = ResourceFactory::class;

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
     * @param Resource $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'      => $data['name'],
            ':quantity'  => $data['quantity'],
            ':status'    => $data['status'],
            ':shared'    => $data['shared'] ? $data['shared'] : null,
            ':countAdditionalPeople' => $data['countAdditionalPeople'] ? 1 : 0
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO 
                {$this->table} 
                (
                `name`, 
                `quantity`,
                `status`, 
                `shared`,
                 `countAdditionalPeople`
                ) VALUES (
                :name,
                :quantity,
                :status,
                :shared,
                :countAdditionalPeople
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
     * @param int     $resourceId
     * @param Resource $entity
     *
     * @throws QueryExecutionException
     */
    public function update($resourceId, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'             => $data['name'],
            ':quantity'         => $data['quantity'],
            ':status'           => $data['status'],
            ':shared'           => $data['shared'] ? $data['shared'] : null,
            ':countAdditionalPeople' => $data['countAdditionalPeople'] ? 1 : 0,
            ':id'               => $resourceId
        ];


        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `name`              = :name,
                `quantity`          = :quantity,
                `status`            = :status,
                `shared`            = :shared,
                `countAdditionalPeople` = :countAdditionalPeople
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
     * @param int $resourceId
     * @param int $status
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function updateStatusById($resourceId, $status)
    {
        $params = [
            ':id'     => $resourceId,
            ':status' => $status
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `status` = :status
                WHERE id = :id"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }


    /**
     * @param $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByCriteria($criteria)
    {
        $params = [];

        $where = [];

        if (!empty($criteria['search'])) {
            $params[':search'] = "%{$criteria['search']}%";

            $where[] = 'r.name LIKE :search';
        }

        if (!empty($criteria['services'])) {
            $query = [];
            foreach ((array)$criteria['services'] as $index => $value) {
                $param   = ':service' . $index;
                $query[] = $param;

                $params[$param] = $value;
            }
            $where[] = 're.entityId IN (' . implode(', ', $query) . ') AND re.entityType="service"';
        }

        if (!empty($criteria['locations'])) {
            $query = [];
            foreach ((array)$criteria['locations'] as $index => $value) {
                $param   = ':location' . $index;
                $query[] = $param;

                $params[$param] = $value;
            }
            $where[] = 're.entityId IN (' . implode(', ', $query) . ') AND re.entityType="location"';
        }

        if (!empty($criteria['employees'])) {
            $query = [];
            foreach ((array)$criteria['employees'] as $index => $value) {
                $param   = ':employee' . $index;
                $query[] = $param;

                $params[$param] = $value;
            }
            $where[] = 're.entityId IN (' . implode(', ', $query) . ') AND re.entityType="employee"';
        }

        if (!empty($criteria['status'])) {
            $params[':status'] = $criteria['status'];

            $where[] = 'r.status = :status';
        }

        $where = $where ? ' AND ' . implode(' AND ', $where) : '';

        $resourceEntitiesTable = ResourcesToEntitiesTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                r.id AS resource_id,
                r.name AS resource_name,
                r.quantity AS resource_quantity,
                r.status AS resource_status,
                r.shared AS resource_shared,
                r.countAdditionalPeople AS resource_countAdditionalPeople,
                
                re.id AS resource_entity_id,
                re.resourceId AS resource_entity_resourceId,
                re.entityId AS resource_entity_entityId,
                re.entityType AS resource_entity_entityType
                
                FROM {$this->table} r
                LEFT JOIN {$resourceEntitiesTable} re ON re.resourceId = r.id
                WHERE 1 = 1 {$where}
                "
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by criteria in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param $id
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getById($id)
    {
        $params[':id'] = $id;

        $resourceEntitiesTable = ResourcesToEntitiesTable::getTableName();

        try {
            $statement = $this->connection->prepare(
                "SELECT
                r.id AS resource_id,
                r.name AS resource_name,
                r.quantity AS resource_quantity,
                r.status AS resource_status,
                r.shared AS resource_shared,
                r.countAdditionalPeople AS resource_countAdditionalPeople,
                
                re.id AS resource_entity_id,
                re.resourceId AS resource_entity_resourceId,
                re.entityId AS resource_entity_entityId,
                re.entityType AS resource_entity_entityType
                
                FROM {$this->table} r
                LEFT JOIN {$resourceEntitiesTable} re ON re.resourceId = r.id
                WHERE r.id = :id"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws QueryExecutionException|InvalidArgumentException
     */
    public function delete($id)
    {
        $resourceToEntities = ResourcesToEntitiesTable::getTableName();

        $params = [
            ':id'  => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE id = :id"
            );
            $success1  = $statement->execute($params);
            $statement = $this->connection->prepare(
                "DELETE FROM {$resourceToEntities} WHERE resourceId = :id"
            );
            $success2  = $statement->execute($params);

            return $success1 && $success2;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
