<?php

namespace AmeliaBooking\Infrastructure\Repository\Location;

use AmeliaBooking\Domain\Entity\Location\ProviderLocation;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class ProviderLocationRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Location
 */
class ProviderLocationRepository extends AbstractRepository
{
    public const FACTORY = ProviderLocationRepository::class;

    /**
     * @param ProviderLocation $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':userId'     => $data['userId'],
            ':locationId' => $data['locationId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table}
                (`userId`, `locationId`)
                VALUES
                (:userId, :locationId)"
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
     * @param int $userId
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function delete($userId)
    {
        try {
            $statement = $this->connection->prepare("DELETE FROM {$this->table} WHERE userId = :userId");
            $statement->bindParam(':userId', $userId);
            return $statement->execute();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param ProviderLocation $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function update($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':userId'     => $data['userId'],
            ':locationId' => $data['locationId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET `locationId` = :locationId
                WHERE userId = :userId"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__, $e->getCode(), $e);
        }

        return $this->connection->lastInsertId();
    }
}
