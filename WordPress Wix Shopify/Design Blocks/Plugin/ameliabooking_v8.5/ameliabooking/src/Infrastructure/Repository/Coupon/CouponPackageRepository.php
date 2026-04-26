<?php

namespace AmeliaBooking\Infrastructure\Repository\Coupon;

use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;

/**
 * Class CouponPackageRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Coupon
 */
class CouponPackageRepository extends AbstractRepository
{
    /**
     * @param Coupon $coupon
     * @param Package  $package
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function add($coupon, $package)
    {
        $couponData  = $coupon->toArray();
        $packageData = $package->toArray();

        $params = [
            ':couponId' => $couponData['id'],
            ':packageId'  => $packageData['id'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `couponId`,
                `packageId`
                )
                VALUES (
                :couponId, 
                :packageId
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
     * @param int $packageId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteForPackage($couponId, $packageId)
    {
        $params = [
            ':couponId' => $couponId,
            ':packageId'  => $packageId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->table} WHERE couponId = :couponId AND packageId = :packageId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
