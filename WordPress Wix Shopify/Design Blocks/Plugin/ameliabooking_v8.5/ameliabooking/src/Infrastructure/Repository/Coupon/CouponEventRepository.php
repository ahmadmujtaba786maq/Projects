<?php

namespace AmeliaBooking\Infrastructure\Repository\Coupon;

use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CouponEventRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Coupon
 */
class CouponEventRepository extends AbstractRepository
{
    /**
     * @param Coupon $coupon
     * @param Event  $event
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($coupon, $event)
    {
        $couponData = $coupon->toArray();
        $eventData  = $event->toArray();

        $params = [
            ':couponId' => $couponData['id'],
            ':eventId'  => $eventData['id'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `couponId`,
                `eventId`
                )
                VALUES (
                :couponId, 
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
     * @param int $couponId
     * @param int $eventId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteForEvent($couponId, $eventId)
    {
        $params = [
            ':couponId' => $couponId,
            ':eventId'  => $eventId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE couponId = :couponId AND eventId = :eventId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
