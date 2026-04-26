<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\WeekDay;
use AmeliaBooking\Domain\Factory\Schedule\WeekDayFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class WeekDayRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class WeekDayRepository extends AbstractRepository
{
    public const FACTORY = WeekDayFactory::class;

    /**
     * @param WeekDay $entity
     * @param int     $userId
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity, $userId)
    {
        $data = $entity->toArray();

        $params = [
            ':userId'    => $userId,
            ':dayIndex'  => $data['dayIndex'],
            ':startTime' => $data['startTime'],
            ':endTime'   => $data['endTime'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`userId`, `dayIndex`, `startTime`, `endTime`)
                VALUES
                (:userId, :dayIndex, :startTime, :endTime)"
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
     * @param WeekDay $entity
     * @param int     $id
     *
     * @return bool
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
