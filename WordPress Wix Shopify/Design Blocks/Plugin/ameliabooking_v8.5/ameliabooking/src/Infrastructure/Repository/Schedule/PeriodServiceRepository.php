<?php

namespace AmeliaBooking\Infrastructure\Repository\Schedule;

use AmeliaBooking\Domain\Entity\Schedule\PeriodService;
use AmeliaBooking\Domain\Factory\Schedule\PeriodServiceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class PeriodServiceRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Schedule
 */
class PeriodServiceRepository extends AbstractRepository
{
    public const FACTORY = PeriodServiceFactory::class;

    /**
     * @param PeriodService $entity
     * @param int           $periodId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity, $periodId)
    {
        $data = $entity->toArray();

        $params = [
            ':periodId'  => $periodId,
            ':serviceId' => $data['serviceId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`periodId`, `serviceId`)
                VALUES (:periodId, :serviceId)"
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
     * @param PeriodService $entity
     * @param int           $id
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function update($entity, $id)
    {
        $data = $entity->toArray();

        $params = [
            ':id'        => $id,
            ':serviceId' => $data['serviceId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `serviceId` = :serviceId 
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
