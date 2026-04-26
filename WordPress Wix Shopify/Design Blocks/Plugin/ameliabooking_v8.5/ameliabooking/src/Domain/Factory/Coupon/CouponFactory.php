<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Coupon;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\DiscountFixedValue;
use AmeliaBooking\Domain\ValueObjects\DiscountPercentageValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\CouponCode;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class CouponFactory
 *
 * @package AmeliaBooking\Domain\Factory\Coupon
 */
class CouponFactory
{
    /**
     * @param $data
     *
     * @return Coupon
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function create($data)
    {
        $coupon = new Coupon();

        if (isset($data['id'])) {
            $coupon->setId(new Id($data['id']));
        }

        if (isset($data['code'])) {
            $coupon->setCode(new CouponCode($data['code']));
        }

        if (isset($data['discount'])) {
            $coupon->setDiscount(new DiscountPercentageValue($data['discount']));
        }

        if (isset($data['deduction'])) {
            $coupon->setDeduction(new DiscountFixedValue($data['deduction']));
        }

        if (isset($data['limit'])) {
            $coupon->setLimit(new PositiveInteger($data['limit']));
        }

        if (isset($data['status'])) {
            $coupon->setStatus(new Status($data['status']));
        }

        $serviceList = new Collection();

        if (isset($data['serviceList'])) {
            foreach ((array)$data['serviceList'] as $key => $value) {
                $serviceList->addItem(
                    ServiceFactory::create($value),
                    $key
                );
            }
        }

        $eventList = new Collection();

        if (isset($data['eventList'])) {
            foreach ((array)$data['eventList'] as $key => $value) {
                $eventList->addItem(
                    EventFactory::create($value),
                    $key
                );
            }
        }

        $packageList = new Collection();

        if (isset($data['packageList'])) {
            foreach ((array)$data['packageList'] as $key => $value) {
                $packageList->addItem(
                    PackageFactory::create($value),
                    $key
                );
            }
        }

        if (isset($data['customerLimit'])) {
            $coupon->setCustomerLimit(new WholeNumber((int)$data['customerLimit']));
        }

        if (isset($data['notificationInterval'])) {
            $coupon->setNotificationInterval(new WholeNumber($data['notificationInterval']));
        }

        if (isset($data['notificationRecurring'])) {
            $coupon->setNotificationRecurring(new BooleanValueObject($data['notificationRecurring']));
        }

        if (isset($data['used'])) {
            $coupon->setUsed(new WholeNumber($data['used']));
        }

        if (!empty($data['expirationDate'])) {
            if (is_string($data['expirationDate'])) {
                $coupon->setExpirationDate(new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['expirationDate'])));
            } else {
                $coupon->setExpirationDate(new DateTimeValue($data['expirationDate']));
            }
        }

        if (!empty($data['allServices'])) {
            $coupon->setAllServices(new BooleanValueObject($data['allServices']));
        }

        if (!empty($data['allEvents'])) {
            $coupon->setAllEvents(new BooleanValueObject($data['allEvents']));
        }

        if (!empty($data['allPackages'])) {
            $coupon->setAllPackages(new BooleanValueObject($data['allPackages']));
        }

        $coupon->setServiceList($serviceList);
        $coupon->setEventList($eventList);
        $coupon->setPackageList($packageList);

        return $coupon;
    }

    /**
     * @param array $rows
     *
     * @return Collection
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function createCollection($rows)
    {
        $coupons = [];

        foreach ($rows as $row) {
            $couponId  = $row['coupon_id'];
            $serviceId = isset($row['service_id']) ? $row['service_id'] : null;
            $eventId   = isset($row['event_id']) ? $row['event_id'] : null;
            $packageId = isset($row['package_id']) ? $row['package_id'] : null;
            $bookingId = isset($row['booking_id']) ? $row['booking_id'] : null;

            $coupons[$couponId]['id']            = $couponId;
            $coupons[$couponId]['code']          = $row['coupon_code'];
            $coupons[$couponId]['discount']      = $row['coupon_discount'];
            $coupons[$couponId]['deduction']     = $row['coupon_deduction'];
            $coupons[$couponId]['limit']         = $row['coupon_limit'];
            $coupons[$couponId]['customerLimit'] = $row['coupon_customerLimit'];
            $coupons[$couponId]['notificationInterval']  = $row['coupon_notificationInterval'];
            $coupons[$couponId]['notificationRecurring'] = $row['coupon_notificationRecurring'];
            $coupons[$couponId]['status']         = $row['coupon_status'];
            $coupons[$couponId]['expirationDate'] = $row['coupon_expirationDate'];
            $coupons[$couponId]['allServices']    = $row['coupon_allServices'];
            $coupons[$couponId]['allEvents']      = $row['coupon_allEvents'];
            $coupons[$couponId]['allPackages']    = $row['coupon_allPackages'];

            if ($bookingId) {
                $coupons[$couponId]['bookings'][$bookingId] = $bookingId;
            }

            if ($serviceId) {
                $coupons[$couponId]['serviceList'][$serviceId]['id']          = $serviceId;
                $coupons[$couponId]['serviceList'][$serviceId]['name']        = $row['service_name'];
                $coupons[$couponId]['serviceList'][$serviceId]['description'] = $row['service_description'];
                $coupons[$couponId]['serviceList'][$serviceId]['color']       = $row['service_color'];
                $coupons[$couponId]['serviceList'][$serviceId]['status']      = $row['service_status'];
                $coupons[$couponId]['serviceList'][$serviceId]['categoryId']  = $row['service_categoryId'];
                $coupons[$couponId]['serviceList'][$serviceId]['duration']    = $row['service_duration'];
                $coupons[$couponId]['serviceList'][$serviceId]['price']       = $row['service_price'];
                $coupons[$couponId]['serviceList'][$serviceId]['minCapacity'] = $row['service_minCapacity'];
                $coupons[$couponId]['serviceList'][$serviceId]['maxCapacity'] = $row['service_maxCapacity'];
            }

            if ($eventId) {
                $coupons[$couponId]['eventList'][$eventId]['id']    = $eventId;
                $coupons[$couponId]['eventList'][$eventId]['name']  = $row['event_name'];
                $coupons[$couponId]['eventList'][$eventId]['price'] = $row['event_price'];
            }

            if ($packageId) {
                $coupons[$couponId]['packageList'][$packageId]['id']    = $packageId;
                $coupons[$couponId]['packageList'][$packageId]['name']  = $row['package_name'];
                $coupons[$couponId]['packageList'][$packageId]['price'] = $row['package_price'];
            }
        }

        $couponsCollection = new Collection();

        foreach ($coupons as $couponKey => $couponArray) {
            $couponArray['used'] = isset($couponArray['bookings']) ? sizeof($couponArray['bookings']) : 0;

            $couponsCollection->addItem(
                self::create($couponArray),
                $couponKey
            );
        }

        return $couponsCollection;
    }
}
