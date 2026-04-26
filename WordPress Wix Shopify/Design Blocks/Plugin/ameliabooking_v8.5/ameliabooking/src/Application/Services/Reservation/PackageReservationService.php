<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\Deposit\AbstractDepositApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingsLimitReachedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Common\Exceptions\PackageBookingUnavailableException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Package;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomer;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageService;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class PackageReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class PackageReservationService extends AppointmentReservationService
{
    /**
     * @return string
     */
    public function getType()
    {
        return Entities::PACKAGE;
    }

    /**
     * @param array       $appointmentData
     * @param Reservation $reservation
     * @param bool        $save
     *
     * @return void
     *
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function book($appointmentData, $reservation, $save)
    {
        /** @var AbstractPackageApplicationService $packageApplicationService */
        $packageApplicationService = $this->container->get('application.bookable.package');

        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        $clonedCustomFieldsData = $appointmentData['bookings'][0]['customFields'] ?
            json_decode($appointmentData['bookings'][0]['customFields'], true) : null;

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var Package $package */
        $package = $packageRepository->getById($appointmentData['packageId']);

        if ($package->getSharedCapacity() && $package->getSharedCapacity()->getValue()) {
            if ($package->getQuantity()->getValue() < sizeof($appointmentData['package'])) {
                throw new PackageBookingUnavailableException(
                    FrontendStrings::getCommonStrings()['package_booking_unavailable']
                );
            }
        } else {
            $appCount = [];

            foreach ($appointmentData['package'] as $packageData) {
                $appCount[$packageData['serviceId']] = empty($appCount[$packageData['serviceId']]) ?
                    1 : $appCount[$packageData['serviceId']] + 1;
            }

            /** @var PackageService $bookable */
            foreach ($package->getBookable()->getItems() as $bookable) {
                if (
                    !empty($appCount[$bookable->getService()->getId()->getValue()]) &&
                    $appCount[$bookable->getService()->getId()->getValue()] > $bookable->getQuantity()->getValue()
                ) {
                    throw new PackageBookingUnavailableException(
                        FrontendStrings::getCommonStrings()['package_booking_unavailable']
                    );
                }
            }
        }


        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $limitPerCustomerGlobal = $settingsDS->getSetting('roles', 'limitPerCustomerPackage');

        if (!empty($limitPerCustomerGlobal) || !empty($package->getLimitPerCustomer())) {
            $limitPackage = !empty($package->getLimitPerCustomer()) ?
                json_decode($package->getLimitPerCustomer()->getValue(), true) : null;

            $optionEnabled = empty($limitPackage) ? $limitPerCustomerGlobal['enabled'] : $limitPackage['enabled'];

            if ($optionEnabled) {
                /** @var PackageCustomerRepository $packageCustomerRepository */
                $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

                $packageSpecific =
                    !empty($limitPackage['timeFrame']) ||
                    !empty($limitPackage['period']) ||
                    !empty($limitPackage['numberOfApp']);

                $limitPerCustomer = !empty($limitPackage) ? [
                    'numberOfApp' => !empty($limitPackage['numberOfApp']) ?
                        $limitPackage['numberOfApp'] : $limitPerCustomerGlobal['numberOfApp'],
                    'timeFrame'   => !empty($limitPackage['timeFrame']) ?
                        $limitPackage['timeFrame'] : $limitPerCustomerGlobal['timeFrame'],
                    'period'      => !empty($limitPackage['period']) ?
                        $limitPackage['period'] : $limitPerCustomerGlobal['period'],
                ] : $limitPerCustomerGlobal;

                $count = $packageCustomerRepository->getUserPackageCount(
                    $package,
                    $appointmentData['bookings'][0]['customer']['id'],
                    $limitPerCustomer,
                    $packageSpecific
                );

                if ($count >= $limitPerCustomer['numberOfApp']) {
                    throw new BookingsLimitReachedException(FrontendStrings::getCommonStrings()['bookings_limit_reached']);
                }
            }
        }

        $coupon = null;

        if (!empty($appointmentData['couponCode'])) {
            /** @var CouponApplicationService $couponAS */
            $couponAS = $this->container->get('application.coupon.service');

            $coupon = $couponAS->processCoupon(
                $appointmentData['couponCode'],
                [$appointmentData['packageId']],
                Entities::PACKAGE,
                $appointmentData['bookings'][0]['customer']['id'],
                $reservation->hasCouponValidation()->getValue()
            );
        }


        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = $packageApplicationService->addPackageCustomer(
            $package,
            $appointmentData['bookings'][0]['customer']['id'],
            $coupon,
            $save
        );

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageApplicationService->addPackageCustomerServices(
            $package,
            $packageCustomer,
            $appointmentData['packageRules'],
            $save
        );

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
            if (
                !empty($appointmentData['serviceId']) &&
                (int)$appointmentData['serviceId'] === $packageCustomerService->getServiceId()->getValue()
            ) {
                $appointmentData['bookings'][0]['packageCustomerService'] = $packageCustomerService->toArray();

                break;
            }
        }

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        /** @var Collection $customFieldsCollection */
        $customFieldsCollection = $customFieldRepository->getAll();

        if (!empty($appointmentData['bookings'][0]['customer'])) {
            $reservation->setCustomer(UserFactory::create($appointmentData['bookings'][0]['customer']));
        }

        $reservation->setReservation($package);

        $reservation->setBookable($package);

        $reservation->setPackageCustomerServices($packageCustomerServices);
        $reservation->setPackageCustomer($packageCustomer);

        /** @var Collection $packageReservations */
        $packageReservations = new Collection();

        $appointmentsDateTimes = !empty($appointmentData['package']) ? DateTimeService::getSortedDateTimeStrings(
            array_column($appointmentData['package'], 'bookingStart')
        ) : [];

        foreach ($appointmentData['package'] as $index => $packageData) {
            $packageAppointmentData = array_merge(
                $appointmentData,
                [
                    'serviceId'          => $packageData['serviceId'],
                    'providerId'         => $packageData['providerId'],
                    'locationId'         => $packageData['locationId'],
                    'bookingStart'       => $packageData['bookingStart'],
                    'notifyParticipants' => $packageData['notifyParticipants'],
                    'parentId'           => null,
                    'recurring'          => [],
                    'package'            => [],
                    'payment'            => null,
                    'isMollie'           =>
                        !empty($appointmentData['payment']['gateway']) &&
                        $appointmentData['payment']['gateway'] === PaymentType::MOLLIE
                ]
            );

            if (isset($packageData['utcOffset'])) {
                $packageAppointmentData['bookings'][0]['utcOffset'] = $packageData['utcOffset'];
            }

            $packageAppointmentData['bookings'][0]['customFields'] = $clonedCustomFieldsData ?
                $this->getCustomFieldsJsonForService(
                    $clonedCustomFieldsData,
                    $customFieldsCollection,
                    $packageAppointmentData['serviceId']
                ) : null;

            /** @var PackageCustomerService $packageCustomerService */
            foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
                if ((int)$packageData['serviceId'] === $packageCustomerService->getServiceId()->getValue()) {
                    $packageAppointmentData['bookings'][0]['packageCustomerService'] =
                        $packageCustomerService->toArray();

                    break;
                }
            }

            /** @var Reservation $packageReservation */
            $packageReservation = new Reservation();

            try {
                $this->bookSingle(
                    $packageReservation,
                    $packageAppointmentData,
                    DateTimeService::getCustomDateTimeObject($appointmentsDateTimes[0]),
                    DateTimeService::getCustomDateTimeObject($appointmentsDateTimes[sizeof($appointmentsDateTimes) - 1]),
                    $reservation->hasAvailabilityValidation()->getValue(),
                    $save
                );
            } catch (Exception $e) {
                if ($save) {
                    /** @var Reservation $packageReservation */
                    foreach ($packageReservations->getItems() as $packageReservation) {
                        $this->deleteSingleReservation($packageReservation);
                    }

                    $this->deleteSingleReservation($reservation);

                    $packageApplicationService->deletePackageCustomer($packageCustomerServices);
                }

                throw $e;
            }

            $packageReservations->addItem($packageReservation);
        }

        $reservation->setPackageReservations($packageReservations);
        $reservation->setRecurring(new Collection());

        $paymentAmount = $this->getPaymentAmount($packageCustomer, $package)['price'];

        $applyDeposit = $appointmentData['deposit'] && $appointmentData['payment']['gateway'] !== PaymentType::ON_SITE;

        if ($applyDeposit) {
            $paymentDeposit = $depositAS->calculateDepositAmount(
                $paymentAmount,
                $package,
                1
            );

            $appointmentData['payment']['deposit'] = $paymentAmount !== $paymentDeposit;

            $paymentAmount = $paymentDeposit;
        }

        $reservation->setApplyDeposit(new BooleanValueObject($applyDeposit));

        if ($save) {
            /** @var Payment $payment */
            $payment = $this->addPayment(
                null,
                $packageCustomer->getId()->getValue(),
                $appointmentData['payment'],
                $paymentAmount,
                DateTimeService::getNowDateTimeObject(),
                Entities::PACKAGE
            );

            $payments = new Collection();
            $payments->addItem($payment, $payment->getId()->getValue());

            /** @var PackageCustomerService $packageCustomerService */
            foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
                $packageCustomerService->getPackageCustomer()->setPayments($payments);
            }

            $packageCustomer->setPayments($payments);

            do_action('amelia_after_package_booked_frontend', $packageCustomer->toArray());
        }

        if (
            $reservation->hasAvailabilityValidation()->getValue() &&
            $this->hasDoubleBookings(null, $packageReservations)
        ) {
            throw new BookingUnavailableException(
                FrontendStrings::getCommonStrings()['time_slot_unavailable']
            );
        }
    }

    /**
     * @param array $data
     *
     * @return AbstractBookable
     *
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws NotFoundException
     */
    public function getBookableEntity($data)
    {
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        return $bookableAS->getAppointmentService($data['serviceId'], $data['providerId']);
    }

    /**
     * @param Service $bookable
     *
     * @return boolean
     */
    public function isAggregatedPrice($bookable)
    {
        return true;
    }

    /**
     * @param Reservation $reservation
     * @param string      $paymentGateway
     * @param array       $requestData
     *
     * @return array
     *
     */
    public function getWooCommerceData($reservation, $paymentGateway, $requestData)
    {
        /** @var Package $package */
        $package = $reservation->getBookable();

        /** @var AbstractUser $customer */
        $customer = $reservation->getCustomer();

        $packageAppointmentsData = [];

        $customFields = null;

        /** @var Reservation $packageReservation */
        foreach ($reservation->getPackageReservations()->getItems() as $key => $packageReservation) {
            $packageAppointmentData = [
                'serviceId'          => $packageReservation->getReservation()->getServiceId()->getValue(),
                'providerId'         => $packageReservation->getReservation()->getProviderId()->getValue(),
                'locationId'         => $packageReservation->getReservation()->getLocationId() ?
                    $packageReservation->getReservation()->getLocationId()->getValue() : null,
                'bookingStart'       =>
                    $packageReservation->getReservation()->getBookingStart()->getValue()->format('Y-m-d H:i:s'),
                'bookingEnd'         =>
                    $packageReservation->getReservation()->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                'notifyParticipants' => $packageReservation->getReservation()->isNotifyParticipants(),
                'status'             => $packageReservation->getReservation()->getStatus()->getValue(),
                'utcOffset'          => $packageReservation->getBooking()->getUtcOffset() ?
                    $packageReservation->getBooking()->getUtcOffset()->getValue() : null,
            ];

            $packageAppointmentsData[] = $packageAppointmentData;

            $customFields = $packageReservation->getBooking()->getCustomFields();
        }

        return [
            'type'               => Entities::PACKAGE,
            'utcOffset'          => $requestData['utcOffset'],
            'packageRules'       => $requestData['packageRules'],
            'packageId'          => $package->getId()->getValue(),
            'name'               => $package->getName()->getValue(),
            'couponId'           => $reservation->getPackageCustomerServices()->getItems()[0]->getPackageCustomer()->getCouponId() ?
                $reservation->getPackageCustomerServices()->getItems()[0]->getPackageCustomer()->getCouponId()->getValue() : null,
            'couponCode'         => !empty($requestData['couponCode']) ? $requestData['couponCode'] : null,
            'dateTimeValues'     => [],
            'bookings'           => [
                [
                    'customerId'   => $customer->getId() ? $customer->getId()->getValue() : null,
                    'customer'     => [
                        'email'           => $customer->getEmail()->getValue(),
                        'externalId'      => $customer->getExternalId() ? $customer->getExternalId()->getValue() : null,
                        'firstName'       => $customer->getFirstName()->getValue(),
                        'id'              => $customer->getId() ? $customer->getId()->getValue() : null,
                        'lastName'        => $customer->getLastName()->getValue(),
                        'phone'           => $customer->getPhone()->getValue(),
                        'countryPhoneIso' => $customer->getCountryPhoneIso() ?
                            $customer->getCountryPhoneIso()->getValue() : null,
                    ],
                    'persons'      => 1,
                    'extras'       => [],
                    'status'       => null,
                    'utcOffset'    => null,
                    'customFields' => $customFields ? json_decode($customFields->getValue(), true) : null,
                ]
            ],
            'payment'            => [
                'gateway' => $paymentGateway
            ],
            'locale'             => $reservation->getLocale()->getValue(),
            'timeZone'           => $reservation->getTimeZone()->getValue(),
            'recurring'          => [],
            'package'            => $packageAppointmentsData,
            'deposit'            => $reservation->getApplyDeposit()->getValue(),
            'customer'           => array_merge(
                [
                    'locale'     => $reservation->getLocale()->getValue(),
                ],
                $reservation->getCustomer()->toArray()
            )
        ];
    }

    /**
     * @param array $reservation
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getWooCommerceDataFromArray($reservation, $index)
    {
        /** @var array $package */
        $package = $reservation['bookable'];

        /** @var array $customer */
        $customer = $reservation['customer'];

        $packageAppointmentsData = [];

        $customFields = null;

        $booking = $reservation['booking'];

        $customerInfo = !empty($booking['info']) ? json_decode($booking['info'], true) : null;

        /** @var Reservation $packageReservation */
        foreach ($reservation['packageReservations'] as $key => $packageReservation) {
            $packageAppointmentData = [
                'serviceId'          => $packageReservation['serviceId'],
                'providerId'         => $packageReservation['providerId'],
                'locationId'         => $packageReservation['locationId'],
                'bookingStart'       => $packageReservation['bookingStart'],
                'bookingEnd'         => $packageReservation['bookingEnd'],
                'notifyParticipants' => $packageReservation['notifyParticipants'],
                'status'             => $packageReservation['status'],
                'utcOffset'          => $booking['utcOffset'],
            ];

            $packageAppointmentsData[] = $packageAppointmentData;

            $customFields = $booking['customFields'];
        }

        return [
            'type'               => Entities::PACKAGE,
            'utcOffset'          => !empty($booking) ? $booking['utcOffset'] : null,
            'packageId'          => $package['id'],
            'name'               => $package['name'],
            'couponId'           => '',
            'couponCode'         => '',
            'dateTimeValues'     => [],
            'bookings'           => [
                [
                    'customerId'   => $customer['id'],
                    'customer'     => [
                        'email'           => $customer['email'],
                        'externalId'      => $customer['externalId'],
                        'firstName'       => $customer['firstName'],
                        'id'              => $customer['id'],
                        'lastName'        => $customer['lastName'],
                        'phone'           => $customer['phone'],
                        'countryPhoneIso' => $customer['countryPhoneIso'],
                    ],
                    'persons'      => 1,
                    'extras'       => [],
                    'status'       => null,
                    'utcOffset'    => null,
                    'customFields' => $customFields ? json_decode($customFields, true) : null,
                ]
            ],
            'locale'             => $customerInfo ? $customerInfo['locale'] : null,
            'timeZone'           => $customerInfo ? $customerInfo['timeZone'] : null,
            'recurring'          => [],
            'package'            => $packageAppointmentsData,
            'deposit'            => !empty($booking) ? $booking['price'] > $booking['payments'][0]['amount'] : null,
            'customer'           => array_merge(
                [
                    'locale'             => $customerInfo ? $customerInfo['locale'] : null,
                ],
                [
                    'email'           => $customer['email'],
                    'externalId'      => $customer['externalId'],
                    'firstName'       => $customer['firstName'],
                    'id'              => $customer['id'],
                    'lastName'        => $customer['lastName'],
                    'phone'           => $customer['phone'],
                    'countryPhoneIso' => $customer['countryPhoneIso'],
                ]
            )
        ];
    }

    /**
     * @param Reservation  $reservation
     *
     * @return float
     */
    public function getReservationPaymentAmount($reservation)
    {
        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        /** @var Package $bookable */
        $bookable = $reservation->getBookable();

        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = $reservation->getPackageCustomer();

        $paymentAmount = $this->getPaymentAmount($packageCustomer, $bookable)['price'];

        if ($reservation->getApplyDeposit()->getValue()) {
            $paymentAmount = $depositAS->calculateDepositAmount(
                $paymentAmount,
                $bookable,
                1
            );
        }

        return $paymentAmount;
    }

    /**
     * @param Reservation $reservation
     * @param bool        $usePayment
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getProvidersPaymentAmount($reservation, $usePayment = true)
    {
        $amountData = [];

        /** @var Package $bookable */
        $bookable = $reservation->getBookable();

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($reservation->getPackageCustomerServices()->getItems() as $packageCustomerService) {
            /** @var Payment $payment */
            $payment = $usePayment ? $packageCustomerService->getPackageCustomer()->getPayments()->getItem(
                $packageCustomerService->getPackageCustomer()->getPayments()->keys()[0]
            ) : null;

            /** @var PackageService $packageService */
            foreach ($bookable->getBookable()->getItems() as $packageService) {
                /** @var Provider $provider */
                foreach ($packageService->getProviders()->getItems() as $provider) {
                    if (
                        $provider->getStripeConnect() &&
                        $provider->getStripeConnect()->getId() &&
                        $provider->getStripeConnect()->getId()->getValue()
                    ) {
                        $amountData[$provider->getId()->getValue()][0] = [
                            'paymentId' => $payment ? $payment->getId()->getValue() : null,
                            'amount'    => $this->getReservationPaymentAmount($reservation),
                        ];
                    }
                }
            }
        }

        return sizeof($amountData) > 1 ? [] : $amountData;
    }

    /**
     * @param PackageCustomer|null $booking
     * @param Package              $bookable
     * @param bool                 $invoice
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getPaymentAmount($booking, $bookable, $invoice = false)
    {
        /** @var TaxApplicationService $taxApplicationService */
        $taxApplicationService = $this->container->get('application.tax.service');

        /** @var Tax $packageTax */
        $packageTax = $booking ? $this->getTax($booking->getTax()) : null;

        $price = $bookable->getPrice()->getValue();

        if (!$bookable->getCalculatedPrice()->getValue() && $bookable->getDiscount()->getValue()) {
            $subtraction = $price / 100 * ((float)$bookable->getDiscount()->getValue() ?: 0);

            $price = $bookable->getPrice()->getValue() - $subtraction;
        }

        $bookingPrice = $price;

        $hasIncludedTax = $packageTax && !$packageTax->getExcluded()->getValue();
        $hasCoupon = $booking && $booking->getCoupon();

        if ($hasIncludedTax) {
            // Always calculate base price for invoiceSubtotal
            $invoiceSubtotal = $taxApplicationService->getBasePrice($price, $packageTax);

            // Only adjust $price if a coupon is applied
            if ($hasCoupon) {
                $price = $invoiceSubtotal;
            }
        } else {
            $invoiceSubtotal = $price;
        }

        $priceForTax = $invoiceSubtotal;

        $subtraction = 0;

        $reductionAmount = [
            'deduction' => 0,
            'discount'  => 0,
        ];

        if ($booking && $booking->getCoupon()) {
            $reductionAmount['discount'] = $price / 100 * ($booking->getCoupon()->getDiscount()->getValue() ?: 0);

            $reductionAmount['deduction'] = $booking->getCoupon()->getDeduction()->getValue();

            $subtraction = $reductionAmount['discount'] + $reductionAmount['deduction'];

            $price = max($price - $subtraction, 0);

            $priceForTax = $price;
        }

        $invoiceTax = 0;
        if ($packageTax) {
            $invoiceTax = $this->getTaxAmount($packageTax, $priceForTax);
        }

        if ($packageTax && ($packageTax->getExcluded()->getValue() || $subtraction)) {
            $price += $this->getTaxAmount($packageTax, $price);
        }

        $price = (float)max(round($price, 2), 0);

        return [
            'price' => apply_filters('amelia_modify_payment_amount', $price, $booking),
            'discount' => $reductionAmount['discount'],
            'deduction' => $reductionAmount['deduction'],
            'unit_price' => $bookingPrice,
            'qty'        => 1,
            'tax'        => $packageTax ? $this->getTaxAmount($packageTax, $invoiceSubtotal) : 0,
            'subtotal'   => $invoiceSubtotal,
            'total_tax'  => $invoiceTax,
            'tax_rate'   => $packageTax ? $this->getTaxRate($packageTax) : '',
            'tax_type'   => $packageTax ? $packageTax->getType()->getValue() : '',
            'tax_excluded' => $packageTax ? $packageTax->getExcluded()->getValue() : false,
            'full_discount' => $reductionAmount['discount'] + $reductionAmount['deduction']
        ];
    }

    /**
     * @param Payment $payment
     * boolean $fromLink
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getReservationByPayment($payment, $fromLink = false)
    {
        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var PackageRepository $packageRepository */
        $packageRepository = $this->container->get('domain.bookable.package.repository');

        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        /** @var Collection $packageCustomerServices */
        $packageCustomerServices = $packageCustomerServiceRepository->getByCriteria(
            ['packagesCustomers' => [$payment->getPackageCustomerId()->getValue()]]
        );

        $packageId = null;

        $customerId = null;

        $packageCustomerId = null;

        /** @var PackageCustomerService $packageCustomerService */
        foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
            $packageId = $packageCustomerService->getPackageCustomer()->getPackageId()->getValue();

            $customerId = $packageCustomerService->getPackageCustomer()->getCustomerId()->getValue();

            $packageCustomerId = $packageCustomerService->getPackageCustomer()->getId()->getValue();

            break;
        }

        /** @var Package $package */
        $package = $packageId ? $packageRepository->getById($packageId) : null;

        $packageData = [];

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered(
            ['packageCustomerServices' => $packageCustomerServices->keys()]
        );

        $firstBooking = null;

        $firstAppointment = null;

        $firstService = null;

        /** @var Appointment $packageAppointment */
        foreach ($appointments->getItems() as $packageAppointment) {
            if ($packageAppointment->getLocationId()) {
                /** @var Location $location */
                $location = $locationRepository->getById($packageAppointment->getLocationId()->getValue());

                $packageAppointment->setLocation($location);
            }

            /** @var CustomerBooking $packageBooking */
            foreach ($packageAppointment->getBookings()->getItems() as $packageBooking) {
                if (
                    $packageBooking->getPackageCustomerService() &&
                    in_array(
                        $packageBooking->getPackageCustomerService()->getId()->getValue(),
                        $packageCustomerServices->keys()
                    )
                ) {
                    /** @var Service $packageService */
                    $packageService = $bookableAS->getAppointmentService(
                        $packageAppointment->getServiceId()->getValue(),
                        $packageAppointment->getProviderId()->getValue()
                    );

                    if ($firstBooking === null) {
                        $firstBooking = $packageBooking;

                        $this->setToken($firstBooking);

                        $firstAppointment = $packageAppointment;

                        $firstService = $packageService;

                        continue;
                    }

                    $packageData[] = [
                        'type'                     => Entities::APPOINTMENT,
                        Entities::APPOINTMENT      => $packageAppointment->toArray(),
                        Entities::BOOKING          => $packageBooking->toArray(),
                        'appointmentStatusChanged' => true,
                        'utcTime'                  => $this->getBookingPeriods(
                            $packageAppointment,
                            $packageBooking,
                            $packageService
                        ),
                        'isRetry'                  => !$fromLink,
                        'fromLink'                 => $fromLink
                    ];
                }
            }
        }

        /** @var AbstractUser $customer */
        $customer = $customerRepository->getById($customerId);

        $customerCabinetUrl = '';

        if ($customer->getEmail() && $customer->getEmail()->getValue()) {
            /** @var HelperService $helperService */
            $helperService = $this->container->get('application.helper.service');

            $locale = '';

            if ($firstBooking && $firstBooking->getInfo() && $firstBooking->getInfo()->getValue()) {
                $info = json_decode($firstBooking->getInfo()->getValue(), true);

                $locale = !empty($info['locale']) ? $info['locale'] : '';
            }

            $customerCabinetUrl = $helperService->getCustomerCabinetUrl(
                $customer->getEmail()->getValue(),
                'email',
                null,
                null,
                $locale
            );
        }

        $result->setData(
            [
                'type'                     => Entities::APPOINTMENT,
                Entities::APPOINTMENT      => $firstAppointment ? $firstAppointment->toArray() : null,
                Entities::BOOKING          => $firstBooking ? $firstBooking->toArray() : null,
                'customer'                 => $customer->toArray(),
                'packageId'                => $packageId,
                'recurring'                => $packageData,
                'appointmentStatusChanged' => false,
                'utcTime'                  => $firstAppointment && $firstBooking ? $this->getBookingPeriods(
                    $firstAppointment,
                    $firstBooking,
                    $firstService
                ) : [],
                'bookable'                 => $package ? $package->toArray() : null,
                'isRetry'                  => !$fromLink,
                'fromLink'                 => $fromLink,
                'paymentId'                => $payment->getId()->getValue(),
                'packageCustomerId'        => $packageCustomerId,
                'payment'                  => [
                    'id'           => $payment->getId()->getValue(),
                    'amount'       => $payment->getAmount()->getValue(),
                    'gateway'      => $payment->getGateway()->getName()->getValue(),
                    'gatewayTitle' => $payment->getGatewayTitle() ? $payment->getGatewayTitle()->getValue() : '',
                    'invoiceNumber' => $payment->getInvoiceNumber() ? $payment->getInvoiceNumber()->getValue() : '',
                ],
                'customerCabinetUrl'       => $customerCabinetUrl,
            ]
        );

        return $result;
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws QueryExecutionException
     */
    public function manageTaxes(&$data)
    {
        /** @var TaxApplicationService $taxAS */
        $taxAS = $this->container->get('application.tax.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $taxesSettings = $settingsService->getSetting(
            'payments',
            'taxes'
        );

        if ($taxesSettings['enabled']) {
            $data['tax'] = $taxAS->getTaxData(
                $data['packageId'],
                Entities::PACKAGE,
                $taxAS->getAll()
            );
        }
    }

    /**
     * @param int $bookingId
     * @param string $token
     *
     * @return array
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws Exception
     */
    public function deleteBooking($bookingId, $token = null)
    {
        /** @var PackageCustomerRepository $packageCustomerRepository */
        $packageCustomerRepository = $this->container->get('domain.bookable.packageCustomer.repository');

        /** @var BookableApplicationService $bookableApplicationService */
        $bookableApplicationService = $this->container->get('application.bookable.service');

        /** @var PackageCustomer $packageCustomer */
        $packageCustomer = $packageCustomerRepository->getById($bookingId);

        if (!$packageCustomer) {
            throw new NotFoundException('Package customer not found.');
        }

        if ($token && (!$packageCustomer->getToken() || $packageCustomer->getToken()->getValue() !== $token)) {
            throw new AccessDeniedException('Invalid token.');
        }

        $packageCustomerRepository->beginTransaction();

        if (($resultData = $bookableApplicationService->deletePackageCustomer($packageCustomer)) === false) {
            $packageCustomerRepository->rollback();

            throw new \Exception('Could not delete package booking.');
        }

        $packageCustomerRepository->commit();

        return $resultData;
    }
}
