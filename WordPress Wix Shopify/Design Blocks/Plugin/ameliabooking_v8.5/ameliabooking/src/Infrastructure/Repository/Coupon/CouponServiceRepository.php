<?php

namespace AmeliaBooking\Infrastructure\Repository\Coupon;

use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CouponServiceRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Coupon
 */
class CouponServiceRepository extends AbstractRepository
{
    /**
     * @param Coupon  $coupon
     * @param Service $service
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($coupon, $service)
    {
        $couponData  = $coupon->toArray();
        $serviceData = $service->toArray();

        $params = [
            ':couponId'  => $couponData['id'],
            ':serviceId' => $serviceData['id'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `couponId`,
                `serviceId`
                )
                VALUES (
                :couponId, 
                :serviceId
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
     * @param int $serviceId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteForService($couponId, $serviceId)
    {
        $params = [
            ':couponId'  => $couponId,
            ':serviceId' => $serviceId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE couponId = :couponId AND serviceId = :serviceId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
