<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\SpecialDayPeriodLocation;
use AmeliaBooking\Domain\Factory\Schedule\SpecialDayPeriodLocationFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class SpecialDayPeriodLocationRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class SpecialDayPeriodLocationRepository extends AbstractRepository
{
    public const FACTORY = SpecialDayPeriodLocationFactory::class;

    /**
     * @param SpecialDayPeriodLocation $entity
     * @param int                     $periodId
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
     * @param SpecialDayPeriodLocation $entity
     * @param int                     $id
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
