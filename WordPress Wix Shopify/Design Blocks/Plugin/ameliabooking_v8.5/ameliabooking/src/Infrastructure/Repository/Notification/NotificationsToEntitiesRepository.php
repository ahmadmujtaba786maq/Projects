<?php

namespace AmeliaBooking\Infrastructure\Repository\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification\NotificationsToEntitiesTable;

class NotificationsToEntitiesRepository extends AbstractRepository
{
    /**
     * @param $notificationId
     *
     * @return array
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getEntities($notificationId)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT entityId FROM {$this->table} WHERE notificationId = :id"
            );

            $params = [
                ':id' => $notificationId
            ];

            $statement->execute($params);

            $entityRows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get entities in ' . __CLASS__, $e->getCode(), $e);
        }

        return array_column($entityRows, 'entityId');
    }

    /**
     * @param int $notificationId
     * @param int $entityId
     * @param string $entity
     *
     * @return bool
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function removeEntity($notificationId, $entityId, $entity)
    {
        $params = [
            ':notificationId'  => $notificationId,
            ':entity'          => $entity,
            ':entityId'        => $entityId
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE notificationId = :notificationId AND entity = :entity AND entityId = :entityId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }


    /**
     * @param $entityId
     *
     * @return bool
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function removeIfOnly($entityId)
    {
        $notificationsTable = NotificationsTable::getTableName();
        try {
            $statement = $this->connection->prepare(
                "DELETE n FROM {$notificationsTable} n
                 INNER JOIN {$this->table} ne ON n.id = ne.notificationId 
                 WHERE ne.entityId = :id
                 AND NOT EXISTS (SELECT * FROM {$this->table} ne2 WHERE ne2.entityId <> ne.entityId AND ne2.notificationId = n.id)"
            );

            $params = [
                ':id' => $entityId
            ];

            $success1 = $statement->execute($params);

            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE entityId = :id"
            );

            $success2 = $statement->execute($params);

            return $success1 && $success2;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get entities in ' . __CLASS__, $e->getCode(), $e);
        }
    }

    /**
     * @param int $notificationId
     * @param int $entityId
     * @param string $entity
     *
     * @return bool
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function addEntity($notificationId, $entityId, $entity)
    {
        $params = [
            ':notificationId'  => $notificationId,
            ':entity'          => $entity,
            ':entityId'        => $entityId
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} (`notificationId`, `entity`, `entityId`) VALUES (:notificationId, :entity, :entityId)"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
