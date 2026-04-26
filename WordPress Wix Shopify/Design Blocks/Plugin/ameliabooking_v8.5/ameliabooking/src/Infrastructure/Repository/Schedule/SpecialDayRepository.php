<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\SpecialDay;
use AmeliaBooking\Domain\Factory\Schedule\SpecialDayFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class SpecialDayRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class SpecialDayRepository extends AbstractRepository
{
    public const FACTORY = SpecialDayFactory::class;

    /**
     * @param SpecialDay $entity
     * @param int        $userId
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity, $userId)
    {
        $data = $entity->toArray();

        $params = [
            ':userId'    => $userId,
            ':startDate' => $data['startDate'],
            ':endDate'   => $data['endDate'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`userId`, `startDate`, `endDate`)
                VALUES
                (:userId, :startDate, :endDate)"
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
     * @param SpecialDay $entity
     * @param int        $id
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($entity, $id)
    {
        $data = $entity->toArray();

        $params = [
            ':id'        => $id,
            ':startDate' => $data['startDate'],
            ':endDate'   => $data['endDate'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `startDate` = :startDate, `endDate` = :endDate
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
