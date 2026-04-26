<?php

namespace AmeliaBooking\Application\Services\Coupon;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Container;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AbstractCouponApplicationService
 *
 * @package AmeliaBooking\Application\Services\Coupon
 */
abstract class AbstractCouponApplicationService
{
    protected $container;

    /**
     * AbstractCouponApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    /**
     * @param Coupon $coupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    abstract public function add($coupon);

    /**
     * @param Coupon $oldCoupon
     * @param Coupon $newCoupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    abstract public function update($oldCoupon, $newCoupon);

    /**
     * @param Coupon $coupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     */
    abstract public function delete($coupon);

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
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws CouponUnknownException
     * @throws CouponInvalidException
     * @throws CouponExpiredException
     */
    abstract public function processCoupon($couponCode, $entityIds, $entityType, $userId, $inspectCoupon);

    /**
     * @param Coupon $coupon
     * @param int    $userId
     * @param bool   $inspectCoupon
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws CouponInvalidException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws CouponExpiredException
     */
    abstract public function inspectCoupon($coupon, $userId, $inspectCoupon);

    /**
     * @param Coupon   $coupon
     * @param int|null $userId
     *
     * @return int
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    abstract public function getAllowedCouponLimit($coupon, $userId);

    /**
     * @return Collection
     */
    abstract public function getAll();

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    abstract public function getAllByCriteria($criteria = []);
}
