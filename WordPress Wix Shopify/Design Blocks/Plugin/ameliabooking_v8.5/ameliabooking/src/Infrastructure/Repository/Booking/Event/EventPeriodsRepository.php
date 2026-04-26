<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Event;

use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventPeriodFactory;
use AmeliaBooking\Domain\Repository\Booking\Event\EventRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class EventPeriodsRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Event
 */
class EventPeriodsRepository extends AbstractRepository implements EventRepositoryInterface
{
    public const FACTORY = EventPeriodFactory::class;

    /**
     * @param EventPeriod $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':eventId'        => $data['eventId'],
            ':periodStart'    => DateTimeService::getCustomDateTimeInUtc($data['periodStart']),
            ':periodEnd'      => DateTimeService::getCustomDateTimeInUtc($data['periodEnd']),
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `eventId`,
                `periodStart`,
                `periodEnd`
                )
                VALUES (
                :eventId,
                :periodStart,
                :periodEnd
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
     * @param int         $id
     * @param EventPeriod $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'             => $id,
            ':periodStart'    => DateTimeService::getCustomDateTimeInUtc($data['periodStart']),
            ':periodEnd'      => DateTimeService::getCustomDateTimeInUtc($data['periodEnd'])
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `periodStart` = :periodStart,
                `periodEnd` = :periodEnd
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
