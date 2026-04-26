<?php

namespace AmeliaBooking\Application\Services\Bookable;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class BasicPackageApplicationService
 *
 * @package AmeliaBooking\Application\Services\Bookable
 */
class BasicPackageApplicationService extends AbstractPackageApplicationService
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Package $package
     * @param int     $customerId
     * @param Coupon  $coupon
     * @param bool    $save
     *
     * @return PackageCustomer|null
     *
     * @throws ContainerValueNotFoundException
     */
    public function addPackageCustomer($package, $customerId, $coupon, $save)
    {
        return null;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Package         $package
     * @param PackageCustomer $packageCustomer
     * @param array           $packageRules
     * @param bool            $save
     *
     * @return Collection
     *
     * @throws ContainerValueNotFoundException
     */
    public function addPackageCustomerServices($package, $packageCustomer, $packageRules, $save)
    {
        return new Collection();
    }

    /**
     * @param Collection $packageCustomerServices
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     */
    public function deletePackageCustomer($packageCustomerServices)
    {
        return true;
    }

    /**
     * @param Collection $appointments
     *
     * @return void
     */
    public function setPackageBookingsForAppointments($appointments)
    {
    }

    /**
     * @param int  $packageCustomerServiceId
     * @param int  $customerId
     * @param bool $isCabinetBooking
     *
     * @return boolean
     */
    public function isBookingAvailableForPurchasedPackage($packageCustomerServiceId, $customerId, $isCabinetBooking)
    {
        return false;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getPackageStatsData($params)
    {
        return [];
    }

    /**
     * @param array      $packageDatesData
     * @param Collection $appointmentsPackageCustomerServices
     * @param int        $packageCustomerServiceId
     * @param string     $date
     * @param int        $occupiedDuration
     *
     * @return void
     */
    public function updatePackageStatsData(
        &$packageDatesData,
        $appointmentsPackageCustomerServices,
        $packageCustomerServiceId,
        $date,
        $occupiedDuration
    ) {
    }

    /**
     * @param Collection $appointments
     *
     * @return Collection
     *
     * @throws Exception
     */
    public function getPackageCustomerServicesForAppointments($appointments)
    {
        return new Collection();
    }

    /**
     * @param Collection $appointments
     * @param array      $params
     *
     * @return array
     */
    public function getPackageAvailability($appointments, $params)
    {
        return [];
    }

    /**
     * @return array
     */
    public function getPackagesArray()
    {
        return [];
    }

    /**
     * @param array $paymentsData
     * @return void
     */
    public function setPaymentData(&$paymentsData)
    {
    }

    /**
     * @param Collection $appointments
     * @param Collection $packageCustomerServices
     * @param array $packageData
     *
     * @return void
     */
    protected function fixPurchase($appointments, $packageCustomerServices, $packageData)
    {
    }

    /**
     * @param Collection $packageCustomerServices
     * @param Collection $appointments
     *
     * @return array
     */
    public function getPackageUnusedBookingsCount($packageCustomerServices, $appointments)
    {
        return [];
    }

    /**
     * @param  array $package
     *
     * @return array
     */
    public function getOnlyOneEmployee($package)
    {
        return [];
    }
}
