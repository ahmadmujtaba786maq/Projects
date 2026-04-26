<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\TimeOut;
use AmeliaBooking\Domain\Factory\Schedule\TimeOutFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class TimeOutRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class TimeOutRepository extends AbstractRepository
{
    public const FACTORY = TimeOutFactory::class;

    /**
     * @param TimeOut $entity
     * @param int     $weekDayId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity, $weekDayId)
    {
        $data = $entity->toArray();

        $params = [
            ':weekDayId' => $weekDayId,
            ':startTime' => $data['startTime'],
            ':endTime'   => $data['endTime'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`weekDayId`, `startTime`, `endTime`)
                VALUES (:weekDayId, :startTime, :endTime)"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param TimeOut $entity
     * @param int     $id
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function update($entity, $id)
    {
        $data = $entity->toArray();

        $params = [
            ':id'        => $id,
            ':startTime' => $data['startTime'],
            ':endTime'   => $data['endTime'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `startTime` = :startTime, `endTime` = :endTime
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
}
