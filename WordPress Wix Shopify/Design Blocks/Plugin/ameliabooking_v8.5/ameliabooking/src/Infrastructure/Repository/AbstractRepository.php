<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;

/**
 * Class AbstractRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class AbstractRepository
{
    public const FACTORY = '';

    /** @var \PDO */
    protected $connection;

    /** @var string */
    protected $table;

    /**
     * @param Connection $connection
     * @param string     $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection();
        $this->table      = $table;
    }

    /**
     * @param int $id
     *
     * @return Payment|Coupon|Service|Notification|AbstractUser|Location|CustomField
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function getById($id)
    {
        try {
            $statement = $this->connection->prepare($this->selectQuery() . " WHERE {$this->table}.id = :id");
            $statement->bindParam(':id', $id);
            $statement->execute();
            $row = $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            throw new NotFoundException('Data not found in ' . __CLASS__);
        }

        return call_user_func([static::FACTORY, 'create'], $row);
    }

    /**
     * @param array $ids
     *
     * @return Collection
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByIds($ids)
    {
        $params = [];

        foreach ($ids as $index => $id) {
            $params[':id' . $index] = $id;
        }

        $where = " WHERE id IN (" . implode(', ', array_keys($params)) . ')';

        try {
            $statement = $this->connection->prepare($this->selectQuery() . $where);

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        $entities = new Collection();

        foreach ($rows as $row) {
            $entities->addItem(
                call_user_func([static::FACTORY, 'create'], $row),
                $row['id']
            );
        }

        return $entities;
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAll()
    {
        try {
            $statement = $this->connection->query($this->selectQuery());
            $rows      = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAllIndexedById()
    {
        try {
            $statement = $this->connection->query($this->selectQuery());
            $rows      = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $collection = new Collection();
        foreach ($rows as $row) {
            $collection->addItem(
                call_user_func([static::FACTORY, 'create'], $row),
                $row['id']
            );
        }

        return $collection;
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getByFieldValue($field, $value)
    {
        $params = [
            ":$field"  => $value,
        ];

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE {$field} = :{$field}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by field value in ' . __CLASS__, $e->getCode(), $e);
        }

        $collection = new Collection();
        foreach ($rows as $row) {
            $collection->addItem(
                call_user_func([static::FACTORY, 'create'], $row),
                $row['id']
            );
        }

        return $collection;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function delete($id)
    {
        try {
            $statement = $this->connection->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $statement->bindParam(':id', $id);
            return $statement->execute();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int    $entityId
     * @param String $entityColumnName
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getByEntityId($entityId, $entityColumnName)
    {
        $params = [
            ":$entityColumnName"  => $entityId,
        ];

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->table} WHERE {$entityColumnName} = :{$entityColumnName}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * @param int    $entityId
     * @param String $entityColumnName
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteByEntityId($entityId, $entityColumnName)
    {
        $params = [
            ":$entityColumnName"  => $entityId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE {$entityColumnName} = :{$entityColumnName}"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * SET $entityColumnName = $entityColumnValue WHERE $entityColumnName = $entityId
     *
     * @param int    $entityId
     * @param String $entityColumnValue
     * @param String $entityColumnName
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function updateByEntityId($entityId, $entityColumnValue, $entityColumnName)
    {
        $params = [
            ":$entityColumnName"  => $entityId,
        ];

        if ($entityColumnValue !== null) {
            $updateSql = "`{$entityColumnName}` = :value";

            $params[':value'] = $entityColumnValue;
        } else {
            $updateSql = "`{$entityColumnName}` = NULL";
        }

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} SET
                {$updateSql}
                WHERE {$entityColumnName} = :{$entityColumnName}"
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
     * SET $fieldName = $fieldValue WHERE id = $id
     *
     * @param int    $id
     * @param mixed  $fieldValue
     * @param string $fieldName
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function updateFieldById($id, $fieldValue, $fieldName)
    {
        $params = [
            ':id'         => (int)$id,
            ":$fieldName" => $fieldValue
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `$fieldName` = :$fieldName
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
     * SET $fieldName = $fieldValue WHERE $columnName = $columnValue
     *
     * @param string $fieldName
     * @param mixed  $fieldValue
     * @param string $columnName
     * @param mixed  $columnValue
     *
     * @return void
     * @throws QueryExecutionException
     */
    public function updateFieldByColumn($fieldName, $fieldValue, $columnName, $columnValue)
    {
        $params = [
            ':first'  => $fieldValue,
            ':second' => $columnValue,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `$fieldName` = :first
                WHERE $columnName = :second"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @return string
     */
    protected function selectQuery()
    {
        return "SELECT * FROM {$this->table}";
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }

    /**
     * @param int $page
     * @param int $itemsPerPage
     *
     * @return string
     */
    protected function getLimit($page, $itemsPerPage)
    {
        return $page && $itemsPerPage ? 'LIMIT ' . (int)(($page - 1) * $itemsPerPage) . ', ' . (int)$itemsPerPage : '';
    }

    /**
     * @param String $primaryTable
     * @param String $primaryColumn
     * @param String $corruptedTable
     * @param String $corruptedColumn
     * @param String $typeColumn
     * @param String $typeValue
     *
     * @return String
     * @throws QueryExecutionException
     */
    public function getMissingData(
        $primaryTable,
        $primaryColumn,
        $corruptedTable,
        $corruptedColumn,
        $typeColumn,
        $typeValue
    ) {
        try {
            $statement = $this->connection->prepare(
                "SELECT pt.{$primaryColumn} AS {$primaryColumn} FROM {$primaryTable} pt
                 LEFT JOIN {$corruptedTable} ct ON ct.{$corruptedColumn} = pt.{$primaryColumn}
                 WHERE ct.{$corruptedColumn} IS NULL
                   AND pt.{$primaryColumn} IS NOT NULL" .
                ($typeColumn && $typeValue ? " AND {$typeColumn} = '{$typeValue}'" : '')
            );

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        if ($rows) {
            return "Missing {$primaryColumn} (" . implode(', ', array_unique(array_column($rows, $primaryColumn))) . ") in table {$primaryTable}";
        }

        return '';
    }

    /**
     * @return array
     * @throws QueryExecutionException
     */
    public function getIds($criteria = [])
    {
        $where = [];

        $params = [];

        foreach ($criteria as $columnName => $columnValues) {
            foreach ($columnValues as $index => $columnValue) {
                $params[":$columnName$index"] = $columnValue;
            }

            $where[] = "$columnName IN (" . implode(', ', array_keys($params)) . ')';
        }

        $where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT id AS id FROM {$this->table}
                {$where}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * @param int $entityId
     * @param string $errorMessage
     *
     * @throws QueryExecutionException
     */
    public function updateErrorColumn($entityId, $errorMessage)
    {
        $params = [
            ':error' => $errorMessage,
            ':id' => $entityId
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table} e 
                    SET e.error = CONCAT(e.error, ' | ', :error)
                    WHERE e.id=:id;
                "
            );

            $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException(
                'Unable to add error "' . $errorMessage . '" to ' . $this->table . ' with id ' . $entityId . ' in ' . __CLASS__,
                $e->getCode(),
                $e
            );
        }
    }
}
