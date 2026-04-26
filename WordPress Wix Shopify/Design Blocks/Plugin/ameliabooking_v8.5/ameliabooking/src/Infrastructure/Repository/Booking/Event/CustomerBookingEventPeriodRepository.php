<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Event;

use AmeliaBooking\Domain\Entity\Booking\Event\CustomerBookingEventPeriod;
use AmeliaBooking\Domain\Repository\Booking\Event\EventRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CustomerBookingEventPeriodRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Event
 */
class CustomerBookingEventPeriodRepository extends AbstractRepository implements EventRepositoryInterface
{
    public const FACTORY = CustomerBookingEventPeriod::class;

    /**
     * @param CustomerBookingEventPeriod $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':eventPeriodId'     => $data['eventPeriodId'],
            ':customerBookingId' => $data['customerBookingId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `eventPeriodId`,
                `customerBookingId`
                )
                VALUES (
                :eventPeriodId,
                :customerBookingId
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
     * @param int                        $id
     * @param CustomerBookingEventPeriod $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'                => $id,
            ':eventPeriodId'     => $data['eventPeriodId'],
            ':customerBookingId' => $data['customerBookingId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `eventPeriodId` = :eventPeriodId,
                `customerBookingId` = :customerBookingId
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
