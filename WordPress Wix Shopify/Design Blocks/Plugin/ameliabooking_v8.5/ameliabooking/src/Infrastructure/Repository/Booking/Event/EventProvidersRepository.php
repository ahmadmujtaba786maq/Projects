<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Event;

use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class EventProvidersRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Event
 */
class EventProvidersRepository extends AbstractRepository
{
    /**
     * @param Event    $event
     * @param Provider $provider
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($event, $provider)
    {
        $eventData    = $event->toArray();
        $providerData = $provider->toArray();

        $params = [
            ':userId'  => $providerData['id'],
            ':eventId' => $eventData['id'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `userId`,
                `eventId`
                )
                VALUES (
                :userId, 
                :eventId
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
}
