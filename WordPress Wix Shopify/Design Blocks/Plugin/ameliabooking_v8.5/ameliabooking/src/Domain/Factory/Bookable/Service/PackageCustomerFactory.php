<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Factory\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\WholeNumber;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class PackageCustomerFactory
 *
 * @package AmeliaBooking\Domain\Factory\Bookable\Service
 */
class PackageCustomerFactory
{
    /**
     * @param $data
     *
     * @return PackageCustomer
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = new PackageCustomer();

        if (isset($data['id'])) {
            $packageCustomer->setId(new Id($data['id']));
        }

        if (isset($data['packageId'])) {
            $packageCustomer->setPackageId(new Id($data['packageId']));
        }

        if (isset($data['customerId'])) {
            $packageCustomer->setCustomerId(new Id($data['customerId']));
        }

        if (isset($data['customer'])) {
            $packageCustomer->setCustomer(UserFactory::create($data['customer']));
        }

        if (isset($data['price'])) {
            $packageCustomer->setPrice(new Price($data['price']));
        }

        $payments = new Collection();
        if (!empty($data['payments'])) {
            /** @var array $paymentsList */
            $paymentsList = $data['payments'];
            foreach ($paymentsList as $paymentKey => $payment) {
                $payments->addItem(PaymentFactory::create($payment), $paymentKey);
            }
        }
        $packageCustomer->setPayments($payments);


        if (!empty($data['end'])) {
            $packageCustomer->setEnd(
                new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['end']))
            );
        }

        if (!empty($data['start'])) {
            $packageCustomer->setStart(
                new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['start']))
            );
        }

        if (!empty($data['purchased'])) {
            $packageCustomer->setPurchased(
                new DateTimeValue(DateTimeService::getCustomDateTimeObject($data['purchased']))
            );
        }

        if (!empty($data['status'])) {
            $packageCustomer->setStatus(
                new BookingStatus($data['status'])
            );
        }

        if (isset($data['bookingsCount'])) {
            $packageCustomer->setBookingsCount(new WholeNumber($data['bookingsCount']));
        }

        if (isset($data['couponId'])) {
            $packageCustomer->setCouponId(new Id($data['couponId']));
        }

        if (isset($data['coupon'])) {
            $packageCustomer->setCoupon(CouponFactory::create($data['coupon']));
        }

        if (!empty($data['tax'])) {
            if (is_string($data['tax'])) {
                $packageCustomer->setTax(new Json($data['tax']));
            } elseif (json_encode($data['tax']) !== false) {
                $packageCustomer->setTax(new Json(json_encode($data['tax'])));
            }
        }

        if (isset($data['token'])) {
            $packageCustomer->setToken(new Token($data['token']));
        }

        return $packageCustomer;
    }
}
