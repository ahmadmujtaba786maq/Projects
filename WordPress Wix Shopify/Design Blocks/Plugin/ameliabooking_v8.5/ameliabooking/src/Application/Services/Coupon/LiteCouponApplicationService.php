<?php

namespace AmeliaBooking\Application\Services\Coupon;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class LiteCouponApplicationService
 *
 * @package AmeliaBooking\Application\Services\Coupon
 */
class LiteCouponApplicationService extends AbstractCouponApplicationService
{
    /**
     * @param Coupon $coupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function add($coupon)
    {
        return true;
    }

    /**
     * @param Coupon $oldCoupon
     * @param Coupon $newCoupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function update($oldCoupon, $newCoupon)
    {
        return true;
    }

    /**
     * @param Coupon $coupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function delete($coupon)
    {
        return true;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $couponCode
     * @param array  $entityIds
     * @param string $entityType
     * @param int    $userId
     * @param bool   $inspectCoupon
     *
     * @return Coupon|null
     *
     * @throws ContainerValueNotFoundException
     */
    public function processCoupon($couponCode, $entityIds, $entityType, $userId, $inspectCoupon)
    {
        return null;
    }

    /**
     * @param Coupon $coupon
     * @param int    $userId
     * @param bool   $inspectCoupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function inspectCoupon($coupon, $userId, $inspectCoupon)
    {
        return false;
    }

    /**
     * @param Coupon   $coupon
     * @param int|null $userId
     *
     * @return int
     *
     * @throws ContainerValueNotFoundException
     */
    public function getAllowedCouponLimit($coupon, $userId)
    {
        return 0;
    }

    /**
     * @return Collection
     */
    public function getAll()
    {
        return new Collection();
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     */
    public function getAllByCriteria($criteria = [])
    {
        return new Collection();
    }
}
