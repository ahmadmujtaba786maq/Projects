<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\PeriodLocation;
use AmeliaBooking\Domain\Factory\Schedule\PeriodLocationFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class PeriodLocationRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class PeriodLocationRepository extends AbstractRepository
{
    public const FACTORY = PeriodLocationFactory::class;

    /**
     * @param PeriodLocation $entity
     * @param int           $periodId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity, $periodId)
    {
        $data = $entity->toArray();

        $params = [
            ':periodId'   => $periodId,
            ':locationId' => $data['locationId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`periodId`, `locationId`)
                VALUES (:periodId, :locationId)"
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
     * @param PeriodLocation $entity
     * @param int           $id
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function update($entity, $id)
    {
        $data = $entity->toArray();

        $params = [
            ':id'         => $id,
            ':locationId' => $data['locationId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `locationId` = :locationId 
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
