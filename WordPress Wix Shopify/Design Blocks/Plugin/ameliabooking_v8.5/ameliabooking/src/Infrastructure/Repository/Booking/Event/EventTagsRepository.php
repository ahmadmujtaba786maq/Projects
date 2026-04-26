<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Event;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\EventTag;
use AmeliaBooking\Domain\Factory\Booking\Event\EventTagFactory;
use AmeliaBooking\Domain\Repository\Booking\Event\EventRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class EventTagsRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Event
 */
class EventTagsRepository extends AbstractRepository implements EventRepositoryInterface
{
    public const FACTORY = EventTagFactory::class;

    /**
     * @param EventTag $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':eventId'        => $data['eventId'],
            ':name'           => $data['name'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `eventId`,
                `name`
                )
                VALUES (
                :eventId,
                :name
                )"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int         $id
     * @param EventTag $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'        => $id,
            ':eventId'   => $data['eventId'],
            ':name'      => $data['name']
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `eventId` = :eventId,
                `name` = :name
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
     * @param int $eventId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function deleteByEventId($eventId)
    {
        try {
            $statement = $this->connection->prepare("DELETE FROM {$this->table} WHERE eventId = :eventId");
            $statement->bindParam(':eventId', $eventId);
            return $statement->execute();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAllDistinctByCriteria($criteria)
    {
        $params = [];
        $where  = [];

        if (!empty($criteria['eventIds'])) {
            $queryIds = [];

            foreach ((array)$criteria['eventIds'] as $index => $value) {
                $param          = ':id' . $index;
                $queryIds[]     = $param;
                $params[$param] = $value;
            }

            $where[] = 'eventId IN (' . implode(', ', $queryIds) . ')';
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT DISTINCT(name) FROM {$this->table} {$where} ORDER BY name"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }
}
