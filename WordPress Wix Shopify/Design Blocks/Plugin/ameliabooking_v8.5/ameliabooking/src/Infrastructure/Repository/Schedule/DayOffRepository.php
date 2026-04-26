<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\DayOff;
use AmeliaBooking\Domain\Factory\Schedule\DayOffFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class DayOffRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class DayOffRepository extends AbstractRepository
{
    public const FACTORY = DayOffFactory::class;

    /**
     * @param DayOff $entity
     * @param int    $userId
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity, $userId)
    {
        $data = $entity->toArray();

        $params = [
            ':userId'    => $userId,
            ':name'      => $data['name'],
            ':startDate' => $data['startDate'],
            ':endDate'   => $data['endDate'],
            ':repeat'    => $data['repeat'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`userId`, `name`, `startDate`, `endDate`, `repeat`)
                VALUES
                (:userId, :name, :startDate, :endDate, :repeat)"
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
     * @param DayOff $entity
     * @param int    $id
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($entity, $id)
    {
        $data = $entity->toArray();

        $params = [
            ':id'        => $id,
            ':name'      => $data['name'],
            ':startDate' => $data['startDate'],
            ':endDate'   => $data['endDate'],
            ':repeat'    => $data['repeat'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `name` = :name, `startDate` = :startDate, `endDate` = :endDate, `repeat` = :repeat
                WHERE id = :id"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add save in ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
