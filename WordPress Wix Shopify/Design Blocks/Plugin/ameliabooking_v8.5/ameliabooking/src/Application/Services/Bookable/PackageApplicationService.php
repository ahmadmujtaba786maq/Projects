<?php

namespace AmeliaBooking\Application\Services\Bookable;

use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageService;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageCustomerFactory;
use AmeliaBooking\Domain\Factory\Bookable\Service\PackageCustomerServiceFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Coupon\CouponRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use Exception;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PackageApplicationService
 *
 * @package AmeliaBooking\Application\Services\Bookable
 */
class PackageApplicationService extends AbstractPackageApplicationService
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Package     $package
     * @param int         $customerId
     * @param Coupon|null $coupon
     * @param bool        $save
     *
     * @return PackageCustomer
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function addPackageCustomer($package, $customerId, $coupon, $save)
    {
        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::PACKAGE);

        $endDateTime = null;

        if ($package->getEndDate()) {
            $endDateTime = $package->getEndDate()->getValue();
        } elseif ($package->getDurationCount()) {
            $endDateTime = DateTimeService::getNowDateTimeObject()
                ->modify("+{$package->getDurationCount()->getValue()} {$package->getDurationType()->getValue()}");
        }

        $startDateTimeString = DateTimeService::getNowDateTimeInUtc();

        $packageCustomerData = [
            'customerId'    => $customerId,
            'packageId'     => $package->getId()->getValue(),
            'price'         => $reservationService->getPaymentAmount(null, $package)['price'],
            'end'           => $endDateTime ? $endDateTime->format('Y-m-d H:i:s') : null,
            'start'         => $startDateTimeString,
            'purchased'     => $startDateTimeString,
            'bookingsCount' => $package->getSharedCapacity() && $package->getSharedCapacity()->getValue() ?
                $package->getQuantity()->getValue() : 0,
            'couponId'      => $coupon ? $coupon->getId()->getValue() : null,
            'coupon'        => $coupon ? $coupon->toArray() : null,
        ];

        $reservationService->manageTaxes($packageCustomerData);

        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = PackageCustomerFactory::create($packageCustomerData);

        $price = $reservationService->getPaymentAmount(null, $package)['price'];

        $packageCustomer->setPrice(new Price($price));

        if ($save) {
            $packageCustomerArray = $packageCustomer->toArray();

            $packageCustomerArray = apply_filters('amelia_before_package_customer_added_filter', $packageCustomerArray);

            do_action('amelia_before_package_customer_added', $packageCustomerArray);

            $packageCustomer = PackageCustomerFactory::create($packageCustomerArray);
            $packageCustomer->setToken(new Token());

            $packageCustomerId = $packageCustomerRepository->add($packageCustomer);

            $packageCustomer->setId(new Id($packageCustomerId));
        }

        return $packageCustomer;
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
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function addPackageCustomerServices($package, $packageCustomer, $packageRules, $save)
    {
        $packageCustomerServices = new Collection();

        /** @var PackageService $packageService */
        foreach ($package->getBookable()->getItems() as $packageService) {
            $serviceIndex = array_search(
                $packageService->getService()->getId()->getValue(),
                array_column($packageRules, 'serviceId'),
                false
            );

            $packageData = [
                'serviceId'    => $packageService->getService()->getId()->getValue(),
                'providerId'   =>  $serviceIndex !== false && $packageRules[$serviceIndex]['providerId']
                        ? $packageRules[$serviceIndex]['providerId'] : null,
                'locationId'        => $serviceIndex !== false && $packageRules[$serviceIndex]['locationId']
                    ? $packageRules[$serviceIndex]['locationId'] : null,
                'bookingsCount'     => $package->getSharedCapacity() && $package->getSharedCapacity()->getValue() ?
                    0 : $packageService->getQuantity()->getValue(),
                'packageCustomer'   => $packageCustomer->toArray(),
            ];

            $packageCustomerService = $this->createPackageCustomerService($save, $packageData);

            $packageCustomerServices->addItem($packageCustomerService);
        }

        return $packageCustomerServices;
    }


    /**
     * @param bool         $save
     * @param array $packageData
     *
     * @return PackageCustomerService
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function createPackageCustomerService($save, $packageData)
    {
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        $packageCustomerService = PackageCustomerServiceFactory::create($packageData);

        if ($save) {
            $packageCustomerServiceId = $packageCustomerServiceRepository->add($packageCustomerService);

            $packageCustomerService->setId(new Id($packageCustomerServiceId));
        }

        return $packageCustomerService;
    }

    /**
     * @param Collection $packageCustomerServices
     *
     * @return boolean
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function deletePackageCustomer($packageCustomerServices)
    {
        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
            $id = $packageCustomerService->getPackageCustomer()->getId()->getValue();

            /** @var Collection $payments */
            $payments = $paymentRepository->getByEntityId($id, 'packageCustomerId');

            /** @var Payment $payment */
            foreach ($payments->getItems() as $payment) {
                if (!$paymentAS->delete($payment)) {
                    return false;
                }
            }

            if (
                $packageCustomerServiceRepository->deleteByEntityId($id, 'packageCustomerId') &&
                $packageCustomerRepository->delete($id)
            ) {
                return true;
            }

            return true;
        }

        return false;
    }

    /**
     * @param Collection $appointments
     *
     * @return void
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function setPackageBookingsForAppointments($appointments)
    {
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        $packageCustomerServiceIds = [];

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $customerBooking */
            foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                if (
                    $customerBooking->getPackageCustomerService() &&
                    $customerBooking->getPackageCustomerService()->getId()
                ) {
                    $packageCustomerServiceIds[] =
                        $customerBooking->getPackageCustomerService()->getId()->getValue();
                }
            }
        }

        if ($packageCustomerServiceIds) {
            $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
                [
                    'ids'   => $packageCustomerServiceIds,
                ]
            );

            /** @var Appointment $appointment */
            foreach ($appointments->getItems() as $appointment) {
                /** @var CustomerBooking $customerBooking */
                foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                    if (
                        $customerBooking->getPackageCustomerService() &&
                        $customerBooking->getPackageCustomerService()->getId() &&
                        $packageCustomerServices->keyExists($customerBooking->getPackageCustomerService()->getId()->getValue())
                    ) {
                        $customerBooking->setPackageCustomerService(
                            $packageCustomerServices->getItem(
                                $customerBooking->getPackageCustomerService()->getId()->getValue()
                            )
                        );

                        if ($customerBooking->getPackageCustomerService()->getPackageCustomer()->getPayments()) {
                            $customerBooking->setPayments($customerBooking->getPackageCustomerService()->getPackageCustomer()->getPayments());
                        }

                        if ($customerBooking->getPackageCustomerService()->getPackageCustomer()->getCouponId()) {
                            /** @var CouponRepository $couponRepository */
                            $couponRepository = $this->container->get('domain.coupon.repository');

                            /** @var Id  $couponId */
                            $couponId = $customerBooking->getPackageCustomerService()->getPackageCustomer()->getCouponId()->getValue();

                            $coupon = $couponRepository->getById($couponId);

                            $coupon->setServiceList(new Collection());
                            $coupon->setEventList(new Collection());
                            $coupon->setPackageList(new Collection());

                            $customerBooking->getPackageCustomerService()->getPackageCustomer()->setCoupon($coupon);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int  $packageCustomerServiceId
     * @param int  $customerId
     * @param bool $isCabinetBooking
     *
     * @return boolean
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function isBookingAvailableForPurchasedPackage($packageCustomerServiceId, $customerId, $isCabinetBooking)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            [
                'ids'        => [$packageCustomerServiceId],
                'customerId' => $customerId,
            ]
        );

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $key => $packageCustomerService) {
            /** @var Collection $payments */
            $payments = $packageCustomerService->getPackageCustomer()->getPayments();

            if (
                $payments && $payments->length() > 0 &&
                in_array($payments->getItem($payments->keys()[0])->getGateway()->getName()->getValue(), [PaymentType::MOLLIE]) &&
                $payments->getItem($payments->keys()[0])->getStatus()->getValue() === PaymentStatus::PENDING
            ) {
                $packageCustomerServices->deleteItem($key);
            }

            if (
                $packageCustomerService->getPackageCustomer() &&
                $packageCustomerService->getPackageCustomer()->getId() &&
                (
                    $isCabinetBooking &&
                    $packageCustomerService->getPackageCustomer()->getStatus() &&
                    $packageCustomerService->getPackageCustomer()->getStatus()->getValue() === BookingStatus::CANCELED
                )
            ) {
                return false;
            }
        }

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered(
            [
                'customerId' => $customerId,
            ]
        );

        $availablePackageBookings = $packageCustomerServices->length() ?
            $this->getPackageUnusedBookingsCount(
                $packageCustomerServices,
                $appointments
            ) : [];

        foreach ($availablePackageBookings as $customerData) {
            foreach ($customerData['packages'] as $packageData) {
                foreach ($packageData['services'] as $serviceData) {
                    foreach ($serviceData['bookings'] as $bookingData) {
                        if ($bookingData['count'] > 0) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param array $params
     *
     * @return array
     *
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws Exception
     */
    public function getPackageStatsData($params)
    {
        $packageDatesData = [];

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var Collection $purchasedPackageCustomerServices */
        $purchasedPackageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            [
                'purchased' => $params['dates'],
            ]
        );

        $packageDatesDataCustomer = [];

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($purchasedPackageCustomerServices->getItems() as $packageCustomerService) {
            $dateString = $packageCustomerService->getPackageCustomer()->getPurchased()->getValue()->format('Y-m-d');

            $packageId = $packageCustomerService->getPackageCustomer()->getPackageId()->getValue();

            $packageCustomerId = $packageCustomerService->getPackageCustomer()->getId()->getValue();

            if (empty($packageDatesDataCustomer[$packageCustomerId])) {
                $packageCustomerRevenue = 0;

                foreach ($packageCustomerService->getPackageCustomer()->getPayments()->getItems() as $payment) {
                    $packageCustomerRevenue += $payment->getAmount()->getValue();
                }

                if (empty($packageDatesData[$dateString][$packageId])) {
                    $packageDatesData[$dateString][$packageId] = [
                        'count'     => 0,
                        'purchased' => 1,
                        'revenue'   => $packageCustomerRevenue,
                        'occupied'  => 0,
                    ];
                } else {
                    $packageDatesData[$dateString][$packageId]['purchased']++;

                    $packageDatesData[$dateString][$packageId]['revenue'] += $packageCustomerRevenue;
                }
            }

            $packageDatesDataCustomer[$packageCustomerId] = true;
        }

        return $packageDatesData;
    }

    /**
     * @param array      $packageDatesData
     * @param Collection $appointmentsPackageCustomerServices
     * @param int        $packageCustomerServiceId
     * @param string     $date
     * @param int        $occupiedDuration
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function updatePackageStatsData(
        &$packageDatesData,
        $appointmentsPackageCustomerServices,
        $packageCustomerServiceId,
        $date,
        $occupiedDuration
    ) {
        if ($appointmentsPackageCustomerServices->keyExists($packageCustomerServiceId)) {
            $packageCustomerService = $appointmentsPackageCustomerServices->getItem(
                $packageCustomerServiceId
            );

            $packageId = $packageCustomerService->getPackageCustomer()->getPackageId()->getValue();

            if (empty($packageDatesData[$date][$packageId])) {
                $packageDatesData[$date][$packageId] = [
                    'count'     => 1,
                    'purchased' => 0,
                    'revenue'   => 0,
                    'occupied'  => $occupiedDuration
                ];
            } else {
                $packageDatesData[$date][$packageId]['count']++;

                $packageDatesData[$date][$packageId]['occupied'] += $occupiedDuration;
            }
        }
    }

    /**
     * @param Collection $appointments
     *
     * @return Collection
     *
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws Exception
     */
    public function getPackageCustomerServicesForAppointments($appointments)
    {
        $packageCustomerServiceIds = [];

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($booking->getPackageCustomerService()) {
                    $packageCustomerServiceIds[$booking->getPackageCustomerService()->getId()->getValue()] = true;
                }
            }
        }

        /** @var Collection $appointmentsPackageCustomerServices */
        $appointmentsPackageCustomerServices = $packageCustomerServiceIds ? $packageCustomerServiceRepository->getByCriteria(
            [
                'ids' => array_keys($packageCustomerServiceIds),
            ]
        ) : new Collection();

        return $appointmentsPackageCustomerServices;
    }

    /**
     * @param Collection $appointments
     * @param array      $params
     *
     * @return array
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getPackageAvailability($appointments, $params)
    {
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            [
                'packageCustomerIds' => !empty($params['packageCustomerIds']) ? $params['packageCustomerIds'] : [],
                'purchased'          => !empty($params['purchased']) ? $params['purchased'] : [],
                'customerId'         => !empty($params['customerId']) ? $params['customerId'] : null,
                'packages'           => !empty($params['packageId']) ? [$params['packageId']] : []
            ]
        );

        if (empty($params['managePackagePage'])) {

            /** @var PackageCustomerService $packageCustomerService */
            foreach ($packageCustomerServices->getItems() as $key => $packageCustomerService) {
                /** @var Collection $payments */
                $payments = $packageCustomerService->getPackageCustomer()->getPayments();

                if (
                    $payments && $payments->length() > 0 &&
                    in_array($payments->getItem($payments->keys()[0])->getGateway()->getName()->getValue(), [PaymentType::MOLLIE]) &&
                    $payments->getItem($payments->keys()[0])->getStatus()->getValue() === PaymentStatus::PENDING
                ) {
                    $packageCustomerServices->deleteItem($key);
                }
            }
        }

        $params['packageCustomerServices'] = $packageCustomerServices->keys();

        /** @var Collection $packageAppointments */
        $packageAppointments = $packageCustomerServices->length() ?
            $appointmentRepository->getFiltered($params) : new Collection();

        /** @var Appointment $appointment */
        foreach ($packageAppointments->getItems() as $key => $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($booking->getPackageCustomerService()) {
                    /** @var PackageCustomerService $packageCustomerService */
                    $packageCustomerService = $packageCustomerServices->getItem(
                        $booking->getPackageCustomerService()->getId()->getValue()
                    );

                    $booking->getPackageCustomerService()->setPackageCustomer(
                        $packageCustomerService->getPackageCustomer()
                    );
                }
            }

            $appointments->addItem($appointment, $key);
        }

        return $packageCustomerServices->length() ?
            $this->getPackageUnusedBookingsCount(
                $packageCustomerServices,
                $appointments
            ) : [];
    }

    /**
     * @return array
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getPackagesArray()
    {
        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var Collection $packages */
        $packages = $packageRepository->getByCriteria([]);

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $packagesArray = [];

        /** @var Package $package */
        foreach ($packages->getItems() as $package) {
            if ($package->getSettings() && json_decode($package->getSettings()->getValue(), true) === null) {
                $package->setSettings(null);
            }

            $packagesArray[] = array_merge(
                $package->toArray(),
                [
                    'available' =>
                        !$package->getEndDate() ||
                        $package->getEndDate()->getValue() > $currentDateTime
                ]
            );
        }

        return $packagesArray;
    }


    /**
     * @param array $params
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getEmptyPackages($params)
    {
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        return $packageCustomerServiceRepository->getByCriteria($params, true);
    }


    /**
     * @param  array $package
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getOnlyOneEmployee($package)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        $provider = null;
        foreach ($package['bookable'] as $bookable) {
            if (!empty($bookable['providers'])) {
                if (sizeof($bookable['providers']) === 1) {
                    if ($provider === null) {
                        $provider = $bookable['providers'][0];
                    } elseif ($provider['id'] !== $bookable['providers'][0]['id']) {
                        return null;
                    }
                } else {
                    return null;
                }
            } else {
                $results = $providerRepository->getFiltered(['services' => [$bookable['service']['id']]], 0);
                if ($results->length() !== 1) {
                    return null;
                } else {
                    if ($provider === null) {
                        $provider = $results->toArray()[0];
                    } elseif ($provider['id'] !== $results->toArray()[0]['id']) {
                        return null;
                    }
                }
            }
        }

        return $provider;
    }

    /**
     * @param array $paymentsData
     * @return void
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function setPaymentData(&$paymentsData)
    {
        $packageCustomerIds = [];

        foreach ($paymentsData as $payment) {
            if (!$payment['customerBookingId']) {
                $packageCustomerIds[] = $payment['packageCustomerId'];
            }
        }

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            [
                'packagesCustomers' => $packageCustomerIds
            ]
        );

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        if ($packageCustomerServices->length()) {
            /** @var Collection $appointments */
            $appointments = $appointmentRepository->getFiltered(
                [
                    'packageCustomerServices' => $packageCustomerServices->keys(),
                ]
            );

            $paymentsIds = array_column($paymentsData, 'id');

            /** @var Appointment $appointment */
            foreach ($appointments->getItems() as $appointment) {
                /** @var CustomerBooking $booking */
                foreach ($appointment->getBookings()->getItems() as $booking) {
                    /** @var PackageCustomerService $packageCustomerService */

                    if ($booking->getPackageCustomerService()) {
                        $packageCustomerService = $packageCustomerServices->getItem(
                            $booking->getPackageCustomerService()->getId()->getValue()
                        );

                        /** @var Collection $payments */
                        $payments = $packageCustomerService->getPackageCustomer()->getPayments();

                        /** @var Payment $payment */
                        foreach ($payments->getItems() as $payment) {
                            if ($payment && ($key = array_search($payment->getId()->getValue(), $paymentsIds)) !== false) {
                                $paymentsData[$paymentsIds[$key]]['bookingStart'] =
                                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

                                $paymentsData[$paymentsIds[$key]]['providers'][$appointment->getProvider()->getId()->getValue()] = [
                                    'id' => $appointment->getProvider()->getId()->getValue(),
                                    'fullName' => $appointment->getProvider()->getFullName(),
                                    'email' => $appointment->getProvider()->getEmail()->getValue(),
                                ];
                            }
                        }
                    }
                }
            }
        }
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
        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        try {
            $hasAlter = false;

            /** @var Appointment $appointment */
            foreach ($appointments->getItems() as $appointment) {
                $serviceId = $appointment->getServiceId()->getValue();

                /** @var CustomerBooking $customerBooking */
                foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                    if (
                        $customerBooking->getPackageCustomerService() &&
                        $packageCustomerServices->keyExists(
                            $customerBooking->getPackageCustomerService()->getId()->getValue()
                        )
                    ) {
                        /** @var PackageCustomerService $packageCustomerService */
                        $packageCustomerService = $packageCustomerServices->getItem(
                            $customerBooking->getPackageCustomerService()->getId()->getValue()
                        );

                        $packageId = $packageCustomerService->getPackageCustomer()->getPackageId()->getValue();

                        $id = $packageCustomerService->getId()->getValue();

                        $customerId = $customerBooking->getCustomerId()->getValue();

                        if (
                            !empty($packageData[$customerId][$serviceId][$packageId][$id]) &&
                            !$packageCustomerService->getPackageCustomer()->getStatus()
                        ) {
                            if ($packageData[$customerId][$serviceId][$packageId][$id]['available'] > 0) {
                                $packageData[$customerId][$serviceId][$packageId][$id]['available']--;
                            } else {
                                foreach ($packageData[$customerId][$serviceId][$packageId] as $pcsId => $value) {
                                    if ($value['available'] > 0) {
                                        $packageData[$customerId][$serviceId][$packageId][$pcsId]['available']--;

                                        $customerBooking->getPackageCustomerService()->setId(new Id($pcsId));

                                        $customerBookingRepository->updateFieldById(
                                            $customerBooking->getId()->getValue(),
                                            $pcsId,
                                            'packageCustomerServiceId'
                                        );

                                        $hasAlter = true;

                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($hasAlter) {
                $alteredPcIds = [];

                /** @var PackageCustomerService $packageCustomerService */
                foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
                    $alteredPcIds[] = $packageCustomerService->getPackageCustomer()->getId()->getValue();

                    $packageCustomerService->getPackageCustomer()->setStatus(
                        new BookingStatus(BookingStatus::APPROVED)
                    );
                }

                foreach (array_unique($alteredPcIds) as $value) {
                    $packageCustomerRepository->updateFieldById(
                        $value,
                        'approved',
                        'status'
                    );
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * @param Collection $packageCustomerServices
     * @param Collection $appointments
     *
     * @return array
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getPackageUnusedBookingsCount($packageCustomerServices, $appointments)
    {
        $packageData = [];

        $couponsIds = [];

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
            /** @var PackageCustomer $packageCustomer */
            $packageCustomer = $packageCustomerService->getPackageCustomer();

            if ($packageCustomer->getCouponId() && $packageCustomer->getCouponId()->getValue()) {
                $couponsIds[$packageCustomer->getCouponId()->getValue()] = true;
            }
        }

        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container->get('domain.coupon.repository');

        /** @var Collection $coupons */
        $coupons = $couponsIds ? $couponRepository->getFiltered(
            ['ids' => array_keys($couponsIds)],
            0
        ) : new Collection();

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
            /** @var PackageCustomer $packageCustomer */
            $packageCustomer = $packageCustomerService->getPackageCustomer();

            $customerId = $packageCustomer->getCustomerId()->getValue();

            $serviceId = $packageCustomerService->getServiceId()->getValue();

            $packageId = $packageCustomer->getPackageId()->getValue();

            $id = $packageCustomerService->getId()->getValue();

            $sharedPackageCustomerServices = $packageCustomer->getBookingsCount() && $packageCustomer->getBookingsCount()->getValue() ?
                array_filter(
                    $packageCustomerServices->toArray(),
                    function ($element) use ($packageCustomer, $packageCustomerService) {
                        return
                            $element['packageCustomer']['id'] === $packageCustomer->getId()->getValue() &&
                            $element['id'] !== $packageCustomerService->getId()->getValue();
                    }
                ) : [];

            /** @var Id  $couponId */
            $couponId = $packageCustomer->getCouponId() ? $packageCustomer->getCouponId()->getValue() : null;

            /** @var Coupon $coupon */
            $coupon = $couponId && $coupons->keyExists($couponId) ? $coupons->getItem($couponId) : null;

            if (
                ($packageCustomer->getEnd() ?
                    $packageCustomer->getEnd()->getValue() > DateTimeService::getNowDateTimeObject() : true) &&
                !isset($packageData[$customerId][$serviceId][$packageId][$id])
            ) {
                $quantity = $packageCustomer->getBookingsCount() && $packageCustomer->getBookingsCount()->getValue() ?
                    $packageCustomer->getBookingsCount()->getValue() :
                    $packageCustomerService->getBookingsCount()->getValue();

                $packageData[$customerId][$serviceId][$packageId][$id] = [
                    'total'      => $quantity,
                    'count'      => $quantity,
                    'employeeId' => $packageCustomerService->getProviderId() ?
                        $packageCustomerService->getProviderId()->getValue() : null,
                    'locationId' => $packageCustomerService->getLocationId() ?
                        $packageCustomerService->getLocationId()->getValue() : null,
                    'serviceId'  => $packageCustomerService->getServiceId() ?
                        $packageCustomerService->getServiceId()->getValue() : null,
                    'start'      => $packageCustomer->getStart() ?
                        $packageCustomer->getStart()->getValue()->format('Y-m-d H:i') : null,
                    'end'        => $packageCustomerService->getPackageCustomer()->getEnd() ?
                        $packageCustomer->getEnd()->getValue()->format('Y-m-d H:i') : null,
                    'purchased'  => $packageCustomer->getPurchased() ?
                        $packageCustomer->getPurchased()->getValue()->format('Y-m-d H:i') : null,
                    'status'     => $packageCustomer->getStatus() ?
                        $packageCustomer->getStatus()->getValue() : 'approved',
                    'packageCustomerId' => $packageCustomer->getId() ?
                        $packageCustomer->getId()->getValue() : null,
                    'available'  => $packageCustomerService->getBookingsCount()->getValue(),
                    'sharedCapacity' => $packageCustomer->getBookingsCount() &&
                        $packageCustomer->getBookingsCount()->getValue(),
                    'sharedPackageCustomerServices' => $sharedPackageCustomerServices,
                    'payments' => $packageCustomer->getPayments()->toArray(),
                    'coupon' => $coupon ?: null,
                    'price'      => $packageCustomer->getPrice()->getValue(),
                    'tax'        => $packageCustomer->getTax()
                        ? json_decode($packageCustomer->getTax()->getValue(), true)
                        : null,
                ];
            }
        }

        $customerData = [];

        if ($packageCustomerServices->length()) {
            $this->fixPurchase($appointments, $packageCustomerServices, $packageData);

            /** @var Appointment $appointment */
            foreach ($appointments->getItems() as $appointment) {
                $serviceId = $appointment->getServiceId()->getValue();

                /** @var CustomerBooking $customerBooking */
                foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                    if (
                        $customerBooking->getPackageCustomerService() &&
                        $packageCustomerServices->keyExists(
                            $customerBooking->getPackageCustomerService()->getId()->getValue()
                        ) &&
                        $customerBooking->getStatus()->getValue() !== BookingStatus::CANCELED &&
                        $customerBooking->getStatus()->getValue() !== BookingStatus::REJECTED
                    ) {
                        /** @var PackageCustomerService $packageCustomerService */
                        $packageCustomerService = $packageCustomerServices->getItem(
                            $customerBooking->getPackageCustomerService()->getId()->getValue()
                        );

                        $packageId = $packageCustomerService->getPackageCustomer()->getPackageId()->getValue();

                        $id = $packageCustomerService->getId()->getValue();

                        $customerId = $customerBooking->getCustomerId()->getValue();

                        if (!array_key_exists($customerId, $customerData)) {
                            $customerData[$customerId] = [
                                $serviceId => [
                                    $packageId => [
                                        $id => 1
                                    ]
                                ]
                            ];
                        } elseif (!array_key_exists($serviceId, $customerData[$customerId])) {
                            $customerData[$customerId][$serviceId] = [
                                $packageId => [
                                    $id => 1
                                ]
                            ];
                        } elseif (!array_key_exists($packageId, $customerData[$customerId][$serviceId])) {
                            $customerData[$customerId][$serviceId][$packageId] = [
                                $id => 1
                            ];
                        } elseif (!array_key_exists($id, $customerData[$customerId][$serviceId][$packageId])) {
                            $customerData[$customerId][$serviceId][$packageId][$id] = 1;
                        } else {
                            $customerData[$customerId][$serviceId][$packageId][$id] += 1;
                        }
                    }
                }
            }
        }

        $result = [];

        foreach ($packageData as $customerId => $customerValues) {
            foreach ($customerValues as $serviceId => $serviceValues) {
                foreach ($serviceValues as $packageId => $packageValues) {
                    foreach ($packageValues as $id => $values) {
                        $bookedCount = !empty($customerData[$customerId][$serviceId][$packageId][$id]) ?
                            $customerData[$customerId][$serviceId][$packageId][$id] : 0;

                        foreach ($values['sharedPackageCustomerServices'] as $sharedService) {
                            $bookedCount += !empty($customerData[$customerId][$sharedService['serviceId']][$packageId][$sharedService['id']]) ?
                                $customerData[$customerId][$sharedService['serviceId']][$packageId][$sharedService['id']] : 0;
                        }

                        $result[$customerId][$packageId][$serviceId][$id] = array_merge(
                            $values,
                            [
                                'total' => $values['total'],
                                'count' => $values['count'] - $bookedCount
                            ]
                        );
                    }
                }
            }
        }

        $parsedResult = [];

        foreach ($result as $customerId => $customerValues) {
            $customerPackagesServices = [
                'customerId' => $customerId,
                'packages'   => []
            ];

            foreach ($customerValues as $packageId => $serviceValues) {
                $packagesServices = [
                    'packageId' => $packageId,
                    'services'  => []
                ];

                foreach ($serviceValues as $serviceId => $packageValues) {
                    $services = [
                        'serviceId' => $serviceId,
                        'bookings'  => []
                    ];

                    foreach ($packageValues as $id => $values) {
                        $booking = array_merge(
                            ['id' => $id],
                            $values
                        );

                        unset($booking['available']);

                        $services['bookings'][] = $booking;
                    }

                    $packagesServices['services'][] = $services;
                }

                $customerPackagesServices['packages'][] = $packagesServices;
            }

            $parsedResult[] = $customerPackagesServices;
        }

        return $parsedResult;
    }
}
