<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\Deposit\AbstractDepositApplicationService;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Tax\TaxApplicationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService as ApplicationTimeSlotService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingsLimitReachedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CouponExpiredException;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Common\Exceptions\PackageBookingUnavailableException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\CustomField\CustomField;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Tax\Tax;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\Booking\AppointmentDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\CustomField\CustomFieldRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AppointmentReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class AppointmentReservationService extends AbstractReservationService
{
    /**
     * @return string
     */
    public function getType()
    {
        return Entities::APPOINTMENT;
    }

    /**
     * @param array       $appointmentData
     * @param Reservation $reservation
     * @param bool        $save
     *
     * @return void
     *
     * @throws CouponExpiredException
     * @throws CouponInvalidException
     * @throws CouponUnknownException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function book($appointmentData, $reservation, $save)
    {
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var CustomFieldRepository $customFieldRepository */
        $customFieldRepository = $this->container->get('domain.customField.repository');

        $this->manageTaxes($appointmentData);

        /** @var Collection $customFieldsCollection */
        $customFieldsCollection = isset($appointmentData['bookings'][0]['customFields']) ?
            $customFieldRepository->getAll() : new Collection();

        $clonedCustomFieldsData = isset($appointmentData['bookings'][0]['customFields']) ?
            json_decode($appointmentData['bookings'][0]['customFields'], true) : null;

        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        if (empty($appointmentData['couponCode']) && array_column($appointmentData['recurring'], 'couponCode')) {
            $appointmentData['couponCode'] = array_column($appointmentData['recurring'], 'couponCode')[0];
        }

        /** @var Coupon $coupon */
        $coupon = !empty($appointmentData['couponCode']) ? $couponAS->processCoupon(
            $appointmentData['couponCode'],
            array_unique(
                array_merge([$appointmentData['serviceId']], array_column($appointmentData['recurring'], 'serviceId'))
            ),
            Entities::APPOINTMENT,
            $appointmentData['bookings'][0]['customerId'],
            $reservation->hasCouponValidation()->getValue()
        ) : null;

        $allowedCouponLimit = $coupon ? $couponAS->getAllowedCouponLimit(
            $coupon,
            $appointmentData['bookings'][0]['customerId']
        ) : 0;

        if ($allowedCouponLimit > 0 && $coupon->getServiceList()->keyExists($appointmentData['serviceId'])) {
            $appointmentData['bookings'][0]['coupon'] = $coupon->toArray();

            $appointmentData['bookings'][0]['couponId'] = $coupon->getId()->getValue();

            $allowedCouponLimit--;
        }

        $appointmentsDateTimes = !empty($appointmentData['recurring']) ? DateTimeService::getSortedDateTimeStrings(
            array_merge(
                [$appointmentData['bookingStart']],
                array_column($appointmentData['recurring'], 'bookingStart')
            )
        ) : [$appointmentData['bookingStart']];

        $appointmentData['bookings'][0]['customFields'] = $clonedCustomFieldsData ?
            $this->getCustomFieldsJsonForService(
                $clonedCustomFieldsData,
                $customFieldsCollection,
                $appointmentData['serviceId']
            ) : null;

        $this->bookSingle(
            $reservation,
            $appointmentData,
            DateTimeService::getCustomDateTimeObject($appointmentsDateTimes[0]),
            DateTimeService::getCustomDateTimeObject($appointmentsDateTimes[sizeof($appointmentsDateTimes) - 1]),
            $reservation->hasAvailabilityValidation()->getValue(),
            $save
        );

        $reservation->setApplyDeposit(new BooleanValueObject($reservation->getBooking()->getDeposit()->getValue()));

        $reservation->setIsCart(
            new BooleanValueObject(
                isset($appointmentData['isCart']) && is_string($appointmentData['isCart']) ?
                    filter_var($appointmentData['isCart'], FILTER_VALIDATE_BOOLEAN) :
                    !empty($appointmentData['isCart'])
            )
        );

        /** @var Payment $payment */
        $payment = $save && $reservation->getBooking() && $reservation->getBooking()->getPayments()->length() ?
            $reservation->getBooking()->getPayments()->getItem(0) : null;

        /** @var Service $bookable */
        $bookable = $reservation->getBookable();

        /** @var Collection $recurringReservations */
        $recurringReservations = new Collection();

        if (!empty($appointmentData['recurring'])) {
            foreach ($appointmentData['recurring'] as $index => $recurringData) {
                $recurringAppointmentData = array_merge(
                    $appointmentData,
                    [
                        'serviceId'    => !empty($recurringData['serviceId']) ?
                            $recurringData['serviceId'] : $appointmentData['serviceId'],
                        'providerId'   => $recurringData['providerId'],
                        'locationId'   => $recurringData['locationId'],
                        'bookingStart' => $recurringData['bookingStart'],
                        'parentId'     => $reservation->getReservation()->getId() ?
                            $reservation->getReservation()->getId()->getValue() : null,
                        'recurring'    => [],
                        'package'      => []
                    ]
                );

                if ($reservation->isCart() && $reservation->isCart()->getValue()) {
                    if (isset($recurringData['extras'])) {
                        $recurringAppointmentData['bookings'][0]['extras'] = $recurringData['extras'];
                    }

                    if (!empty($recurringData['persons'])) {
                        $recurringAppointmentData['bookings'][0]['persons'] = $recurringData['persons'];
                    }

                    if (!empty($recurringData['duration'])) {
                        $recurringAppointmentData['bookings'][0]['duration'] = $recurringData['duration'];
                    }

                    $recurringAppointmentData['bookings'][0]['tax'] = !empty($recurringData['bookings'][0]['tax'])
                        ? $recurringData['bookings'][0]['tax']
                        : null;
                }

                if (!empty($recurringAppointmentData['bookings'][0]['utcOffset'])) {
                    $recurringAppointmentData['bookings'][0]['utcOffset'] = $recurringData['utcOffset'];
                }

                if (
                    $allowedCouponLimit > 0 &&
                    $coupon->getServiceList()->keyExists($recurringAppointmentData['serviceId'])
                ) {
                    $recurringAppointmentData['bookings'][0]['coupon'] = $coupon->toArray();

                    $recurringAppointmentData['bookings'][0]['couponId'] = $coupon->getId()->getValue();

                    $allowedCouponLimit--;
                } else {
                    $recurringAppointmentData['bookings'][0]['coupon'] = null;

                    $recurringAppointmentData['bookings'][0]['couponId'] = null;
                }

                $isRecurringAndOnSite = empty($appointmentData['isCart']) &&
                    $index >= $bookable->getRecurringPayment()->getValue();

                if ($isRecurringAndOnSite) {
                    $recurringAppointmentData['payment']['gateway'] = PaymentType::ON_SITE;

                    $recurringAppointmentData['bookings'][0]['deposit'] = 0;
                }

                $recurringAppointmentData['payment']['wcOrderItemId'] = !empty($recurringData['wcOrderItemId']) ?
                    $recurringData['wcOrderItemId'] : null;

                $recurringAppointmentData['payment']['parentId'] = $payment ? $payment->getId()->getValue() : null;

                $recurringAppointmentData['payment']['invoiceNumber'] =
                    $payment && $payment->getInvoiceNumber() ?
                        $payment->getInvoiceNumber()->getValue() :
                        null;

                /** @var Reservation $recurringReservation */
                $recurringReservation = new Reservation();

                $recurringAppointmentData['bookings'][0]['customFields'] = $clonedCustomFieldsData ?
                    $this->getCustomFieldsJsonForService(
                        $clonedCustomFieldsData,
                        $customFieldsCollection,
                        $recurringAppointmentData['serviceId']
                    ) : null;

                try {
                    $this->bookSingle(
                        $recurringReservation,
                        $recurringAppointmentData,
                        DateTimeService::getCustomDateTimeObject($appointmentsDateTimes[0]),
                        DateTimeService::getCustomDateTimeObject($appointmentsDateTimes[sizeof($appointmentsDateTimes) - 1]),
                        $reservation->hasAvailabilityValidation()->getValue(),
                        $save
                    );
                } catch (Exception $e) {
                    if ($save) {
                        /** @var Reservation $recurringReservation */
                        foreach ($recurringReservations->getItems() as $recurringReservation) {
                            $this->deleteSingleReservation($recurringReservation);
                        }

                        $this->deleteSingleReservation($reservation);
                    }

                    throw $e;
                }

                $recurringReservation->setApplyDeposit(
                    new BooleanValueObject($recurringReservation->getBooking()->getDeposit()->getValue())
                );

                $recurringReservations->addItem($recurringReservation);
            }
        }

        if (
            $reservation->hasAvailabilityValidation()->getValue() &&
            !$settingsDS->getSetting('roles', 'allowAdminBookOverApp') &&
            $this->hasDoubleBookings($reservation, $recurringReservations)
        ) {
            throw new BookingUnavailableException(
                FrontendStrings::getCommonStrings()['time_slot_unavailable']
            );
        }

        $reservation->setRecurring($recurringReservations);
        $reservation->setPackageCustomerServices(new Collection());
        $reservation->setPackageReservations(new Collection());
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Reservation $reservation
     * @param array       $appointmentData
     * @param DateTime    $minimumAppointmentDateTime
     * @param DateTime    $maximumAppointmentDateTime
     * @param bool        $inspectTimeSlot
     * @param bool        $save
     *
     * @return void
     *
     * @throws NotFoundException
     * @throws BookingUnavailableException
     * @throws BookingsLimitReachedException
     * @throws CustomerBookedException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function bookSingle(
        $reservation,
        $appointmentData,
        $minimumAppointmentDateTime,
        $maximumAppointmentDateTime,
        $inspectTimeSlot,
        $save
    ) {
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');
        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $appointmentStatusChanged = false;

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService($appointmentData['serviceId'], $appointmentData['providerId']);

        $appointmentData['bookings'][0]['deposit'] =
            !empty($appointmentData['payment']['gateway']) &&
            $appointmentData['payment']['gateway'] !== PaymentType::ON_SITE &&
            $this->applyDeposit(
                $service,
                !empty($appointmentData['bookings'][0]['deposit'])
            );

        if ($service->getStatus()->getValue() === Status::HIDDEN) {
            throw new BookingUnavailableException(
                FrontendStrings::getCommonStrings()['time_slot_unavailable']
            );
        }

        /** @var Appointment $existingAppointment */
        $existingAppointment = $appointmentAS->getAlreadyBookedAppointment(
            $appointmentData,
            empty($appointmentData['packageBookingFromBackend']),
            $service
        );

        $bookingStatus = $settingsDS
            ->getEntitySettings($service->getSettings())
            ->getGeneralSettings()
            ->getDefaultAppointmentStatus();

        $appointmentData['bookings'][0]['status'] = !empty($appointmentData['packageBookingFromBackend']) ?
            $appointmentData['bookings'][0]['status'] : $bookingStatus;

        if (!empty($appointmentData['payment']['gateway']) && !empty($appointmentData['payment']['orderStatus'])) {
            $appointmentData['bookings'][0]['status'] = $this->getWcStatus(
                Entities::APPOINTMENT,
                $appointmentData['payment']['orderStatus'],
                'booking',
                false
            ) ?: $bookingStatus;
        }

        if (
            (
            (!empty($appointmentData['payment']['gateway']) &&
                in_array($appointmentData['payment']['gateway'], [PaymentType::MOLLIE])) || !empty($appointmentData['isMollie'])
            ) && !(
                !empty($appointmentData['bookings'][0]['packageCustomerService']['id']) &&
                $reservation->getLoggedInUser() &&
                $reservation->getLoggedInUser()->getType() === AbstractUser::USER_ROLE_CUSTOMER
            )
        ) {
            $appointmentData['bookings'][0]['status'] = BookingStatus::PENDING;
        }

        /** @var Provider $provider */
        $provider = $appointmentAS->isPeriodCustomPricing($service)
            ? $userRepository->getById($appointmentData['providerId'])
            : null;

        if ($existingAppointment) {
            /** @var Appointment $appointment */
            $appointment = AppointmentFactory::create($existingAppointment->toArray());

            if (!empty($appointmentData['locationId'])) {
                $appointment->setLocationId(new Id($appointmentData['locationId']));
            }

            $bookingArray =
                apply_filters(
                    'amelia_before_appointment_booking_saved_filter',
                    $appointmentData['bookings'][0],
                    $service->toArray(),
                    $appointment->toArray()
                );

            do_action('amelia_before_appointment_booking_saved', $bookingArray, $service->toArray(), $appointment->toArray());

            /** @var CustomerBooking $booking */
            $booking = CustomerBookingFactory::create($bookingArray);

            $booking->setAppointmentId($appointment->getId());

            $booking->setPrice(
                new Price(
                    $appointmentAS->getBookingPriceForService(
                        $service,
                        $booking,
                        $provider,
                        $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                    )
                )
            );

            $booking->setAggregatedPrice($service->getAggregatedPrice());

            /** @var CustomerBookingExtra $bookingExtra */
            foreach ($booking->getExtras()->getItems() as $bookingExtra) {
                /** @var Extra $selectedExtra */
                $selectedExtra = $service->getExtras()->getItem($bookingExtra->getExtraId()->getValue());

                $bookingExtra->setPrice($selectedExtra->getPrice());
            }

            $maximumDuration = $appointmentAS->getMaximumBookingDuration($appointment, $service);

            if (
                $booking->getDuration() &&
                $booking->getDuration()->getValue() > $maximumDuration
            ) {
                $service->setDuration(new PositiveDuration($maximumDuration));
            }
        } else {
            /** @var Appointment $appointment */
            $appointment = $appointmentAS->build($appointmentData, $service);

            /** @var CustomerBooking $booking */
            $booking = $appointment->getBookings()->getItem($appointment->getBookings()->keys()[0]);

            $service->setDuration(
                new PositiveDuration($appointmentAS->getMaximumBookingDuration($appointment, $service))
            );

            $booking->setAggregatedPrice($service->getAggregatedPrice());
        }


        if (
            $booking->getPackageCustomerService() && $booking->getPackageCustomerService()->getId() === null
            && $booking->getPackageCustomerService()->getPackageCustomer() && $booking->getPackageCustomerService()->getPackageCustomer()->getId()
        ) {
            /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
            $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

            $packageCustomerService = $packageCustomerServiceRepository->getByCriteria(
                [
                    'packagesCustomers' => [$booking->getPackageCustomerService()->getPackageCustomer()->getId()->getValue()],
                    'services'          => [$service->getId()->getValue()]
                ]
            );

            if ($packageCustomerService->length()) {
                $booking->getPackageCustomerService()->setId(new Id($packageCustomerService->toArray()[0]['id']));
            }
        }

        $service->setPrice(
            new Price(
                $appointmentAS->getBookingPriceForService(
                    $service,
                    $booking,
                    $provider,
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                )
            )
        );

        if ($inspectTimeSlot) {
            /** @var ApplicationTimeSlotService $applicationTimeSlotService */
            $applicationTimeSlotService = $this->container->get('application.timeSlot.service');

            // if not new appointment, check if customer has already made booking
            if (
                $appointment->getId() !== null &&
                !$settingsDS->getSetting('appointments', 'bookMultipleTimes')
            ) {
                foreach ($appointment->getBookings()->keys() as $bookingKey) {
                    /** @var CustomerBooking $customerBooking */
                    $customerBooking = $appointment->getBookings()->getItem($bookingKey);

                    if (
                        $customerBooking->getStatus()->getValue() !== BookingStatus::CANCELED &&
                        $booking->getCustomerId() &&
                        $booking->getCustomerId()->getValue() === $customerBooking->getCustomerId()->getValue()
                    ) {
                        throw new CustomerBookedException(
                            FrontendStrings::getCommonStrings()['customer_already_booked_app']
                        );
                    }
                }
            }

            $selectedExtras = [];

            foreach ($booking->getExtras()->keys() as $extraKey) {
                $selectedExtras[] = [
                    'id'       => $booking->getExtras()->getItem($extraKey)->getExtraId()->getValue(),
                    'quantity' => $booking->getExtras()->getItem($extraKey)->getQuantity()->getValue(),
                ];
            }

            if (
                !$applicationTimeSlotService->isSlotFree(
                    $service,
                    $appointment->getBookingStart()->getValue(),
                    $minimumAppointmentDateTime,
                    $maximumAppointmentDateTime,
                    $appointment->getProviderId()->getValue(),
                    $appointment->getLocationId() ? $appointment->getLocationId()->getValue() : null,
                    $selectedExtras,
                    null,
                    $booking->getPersons()->getValue(),
                    empty($appointmentData['packageBookingFromBackend'])
                )
            ) {
                throw new BookingUnavailableException(
                    FrontendStrings::getCommonStrings()['time_slot_unavailable']
                );
            }

            if (
                $booking->getPackageCustomerService() &&
                $booking->getPackageCustomerService()->getId() &&
                !empty($appointmentData['isCabinetBooking'])
            ) {
                /** @var AbstractPackageApplicationService $packageApplicationService */
                $packageApplicationService = $this->container->get('application.bookable.package');

                if (
                    !$packageApplicationService->isBookingAvailableForPurchasedPackage(
                        $booking->getPackageCustomerService()->getId()->getValue(),
                        $booking->getCustomerId()->getValue(),
                        true
                    )
                ) {
                    throw new PackageBookingUnavailableException(
                        FrontendStrings::getCommonStrings()['package_booking_unavailable']
                    );
                }
            }

            $isPackageBooking = $booking->getPackageCustomerService() !== null;

            if (
                !$isPackageBooking &&
                empty($appointmentData['isBackendOrCabinet']) &&
                $this->checkLimitsPerCustomer(
                    $service,
                    $appointmentData['bookings'][0]['customerId'],
                    $appointment->getBookingStart()->getValue()
                )
            ) {
                throw new BookingsLimitReachedException(FrontendStrings::getCommonStrings()['bookings_limit_reached']);
            }
        }

        if ($save) {
            if ($existingAppointment) {
                $appointment->getBookings()->addItem($booking);
                $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

                $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment($service, $bookingsCount);
                $appointment->setStatus(new BookingStatus($appointmentStatus));
                $appointmentStatusChanged = $existingAppointment->getStatus()->getValue() !== BookingStatus::CANCELED &&
                    $appointmentAS->isAppointmentStatusChanged(
                        $appointment,
                        $existingAppointment
                    );

                $appointmentAS->calculateAndSetAppointmentEnd($appointment, $service);

                $appointmentAS->update(
                    $existingAppointment,
                    $appointment,
                    new Collection(),
                    $service,
                    $appointmentData['payment']
                );
            } else {
                $appointmentAS->add(
                    $appointment,
                    $service,
                    !empty($appointmentData['payment']) ? $appointmentData['payment'] : null,
                    !empty($appointmentData['payment']['isBackendBooking'])
                );
            }
        }

        if ($appointment->getLocationId()) {
            /** @var Location $location */
            $location = $locationRepository->getById($appointment->getLocationId()->getValue());

            $appointment->setLocation($location);
        }

        if ($booking->getCustomer()) {
            $reservation->setCustomer($booking->getCustomer());
        }
        $reservation->setBookable($service);
        $reservation->setBooking($booking);
        $reservation->setReservation($appointment);
        $reservation->setIsStatusChanged(new BooleanValueObject($appointmentStatusChanged));

        do_action('amelia_after_appointment_booking_saved', $booking->toArray(), $service->toArray(), $appointment->toArray());
    }

    /**
     * @param Reservation|null $firstReservation
     * @param Collection       $followingReservations
     *
     * @return boolean
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function hasDoubleBookings($firstReservation, $followingReservations)
    {
        $hasDoubledBooking = $firstReservation && $firstReservation->getReservation()->getId() && $this->isDoubleBooking($firstReservation->getReservation());

        if (!$hasDoubledBooking) {
            /** @var Reservation $followingReservation */
            foreach ($followingReservations->getItems() as $followingReservation) {
                if ($followingReservation->getReservation()->getId() && $this->isDoubleBooking($followingReservation->getReservation())) {
                    $hasDoubledBooking = true;

                    break;
                }
            }
        }

        if ($hasDoubledBooking) {
            if ($firstReservation) {
                $this->deleteSingleReservation($firstReservation);
            }

            /** @var Reservation $followingReservation */
            foreach ($followingReservations->getItems() as $followingReservation) {
                $this->deleteSingleReservation($followingReservation);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Appointment $appointment
     *
     * @return boolean
     * @throws QueryExecutionException
     */
    public function isDoubleBooking($appointment)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $bookedAppointments */
        $bookedAppointments = $appointmentRepository->getFiltered(
            [
                'dates'           => [
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i'),
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i')
                ],
                'providers'       => [$appointment->getProviderId()->getValue()],
                'skipServices'    => true,
                'skipProviders'   => true,
                'skipCustomers'   => true,
                'bookingStatuses' => [BookingStatus::APPROVED, BookingStatus::PENDING],
            ]
        );

        /** @var Appointment $bookedAppointment */
        foreach ($bookedAppointments->getItems() as $bookedAppointment) {
            if (
                $bookedAppointment->getId()->getValue() !== $appointment->getId()->getValue() &&
                (
                    $appointment->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $appointment->getStatus()->getValue() === BookingStatus::PENDING
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CustomerBooking $booking
     * @param string          $requestedStatus
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws BookingCancellationException
     */
    public function updateStatus($booking, $requestedStatus)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getById($booking->getAppointmentId()->getValue());

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService(
            $appointment->getServiceId()->getValue(),
            $appointment->getProviderId()->getValue()
        );

        $requestedStatus = $requestedStatus === null ? $settingsDS
            ->getEntitySettings($service->getSettings())
            ->getGeneralSettings()
            ->getDefaultAppointmentStatus() : $requestedStatus;

        if ($requestedStatus === BookingStatus::CANCELED) {
            $minimumCancelTime = $settingsDS
                ->getEntitySettings($service->getSettings())
                ->getGeneralSettings()
                ->getMinimumTimeRequirementPriorToCanceling();

            $this->inspectMinimumCancellationTime($appointment->getBookingStart()->getValue(), $minimumCancelTime);
        }

        $appointment->getBookings()->getItem($booking->getId()->getValue())->setStatus(
            new BookingStatus($requestedStatus)
        );

        $oldBookingStatus = $booking->getStatus()->getValue();

        $booking->setStatus(new BookingStatus($requestedStatus));

        $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenChangingBookingStatus(
            $service,
            $bookingsCount,
            $requestedStatus
        );

        $appointmentAS->calculateAndSetAppointmentEnd($appointment, $service);

        $appointmentRepository->beginTransaction();

        try {
            $bookingRepository->updateStatusById($booking->getId()->getValue(), $requestedStatus);
            $appointmentRepository->updateStatusById($booking->getAppointmentId()->getValue(), $appointmentStatus);
            $appointmentRepository->updateFieldById(
                $appointment->getId()->getValue(),
                DateTimeService::getCustomDateTimeInUtc(
                    $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i:s')
                ),
                'bookingEnd'
            );
        } catch (QueryExecutionException $e) {
            $appointmentRepository->rollback();
            throw $e;
        }

        $appStatusChanged = false;

        if ($appointment->getStatus()->getValue() !== $appointmentStatus) {
            $appointment->setStatus(new BookingStatus($appointmentStatus));

            $appStatusChanged = true;

            /** @var CustomerBooking $customerBooking */
            foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                if (
                    $customerBooking->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $booking->getId()->getValue() === $customerBooking->getId()->getValue()
                ) {
                    $customerBooking->setChangedStatus(new BooleanValueObject(true));
                }
            }
        }

        if (
            (
                ($oldBookingStatus === BookingStatus::CANCELED || $oldBookingStatus === BookingStatus::REJECTED) &&
                ($requestedStatus === BookingStatus::PENDING || $requestedStatus === BookingStatus::APPROVED)
            ) || (
                ($requestedStatus === BookingStatus::CANCELED || $requestedStatus === BookingStatus::REJECTED) &&
                ($oldBookingStatus === BookingStatus::PENDING || $oldBookingStatus === BookingStatus::APPROVED)
            )
        ) {
            $booking->setChangedStatus(new BooleanValueObject(true));

            /** @var CustomerBooking $customerBooking */
            foreach ($appointment->getBookings()->getItems() as $customerBooking) {
                if ($booking->getId()->getValue() === $customerBooking->getId()->getValue()) {
                    $customerBooking->setChangedStatus(new BooleanValueObject(true));
                }
            }
        }

        $appointment->setChangedStatus(new BooleanValueObject($appStatusChanged));

        $appointmentRepository->commit();

        return [
            Entities::APPOINTMENT      => $appointment->toArray(),
            'appointmentStatusChanged' => $appStatusChanged,
            Entities::BOOKING          => $booking->toArray()
        ];
    }

    /**
     * @param Appointment      $reservation
     * @param CustomerBooking  $booking
     * @param Service          $bookable
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getBookingPeriods($reservation, $booking, $bookable)
    {
        $duration = ($booking->getDuration() && $booking->getDuration()->getValue() !== $bookable->getDuration()->getValue())
            ? $booking->getDuration()->getValue() : $bookable->getDuration()->getValue();

        /** @var CustomerBookingExtra $bookingExtra */
        foreach ($booking->getExtras()->getItems() as $bookingExtra) {
            /** @var Extra $extra */
            $extra = $bookable->getExtras()->getItem($bookingExtra->getExtraId()->getValue());

            $duration += ($extra->getDuration() ?
                $bookingExtra->getQuantity()->getValue() * $extra->getDuration()->getValue() : 0);
        }

        return [
            [
                'start' => DateTimeService::getCustomDateTimeInUtc(
                    $reservation->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                ),
                'end'   => DateTimeService::getCustomDateTimeInUtc(
                    DateTimeService::getCustomDateTimeObject(
                        $reservation->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                    )->modify("+{$duration} seconds")->format('Y-m-d H:i:s')
                )
            ]
        ];
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
        return $bookable->getAggregatedPrice()->getValue();
    }

    /**
     * @param BooleanValueObject $bookableAggregatedPrice
     * @param BooleanValueObject $extraAggregatedPrice
     *
     * @return boolean
     */
    public function isExtraAggregatedPrice($extraAggregatedPrice, $bookableAggregatedPrice)
    {
        return $extraAggregatedPrice === null ?
            $bookableAggregatedPrice->getValue() : $extraAggregatedPrice->getValue();
    }

    /**
     * @param Reservation $reservation
     * @param string      $paymentGateway
     * @param array       $requestData
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getWooCommerceData($reservation, $paymentGateway, $requestData)
    {
        /** @var Appointment $appointment */
        $appointment = $reservation->getReservation();

        /** @var Service $service */
        $service = $reservation->getBookable();

        /** @var AbstractUser $customer */
        $customer = $reservation->getCustomer();

        /** @var CustomerBooking $booking */
        $booking = $reservation->getBooking();

        $recurringAppointmentsData = [];

        if ($reservation->getRecurring()) {
            /** @var Reservation $recurringReservation */
            foreach ($reservation->getRecurring()->getItems() as $recurringReservation) {
                $recurringAppointmentData = [
                    'serviceId'          => $recurringReservation->getReservation()->getServiceId()->getValue(),
                    'providerId'         => $recurringReservation->getReservation()->getProviderId()->getValue(),
                    'locationId'         => $recurringReservation->getReservation()->getLocationId() ?
                        $recurringReservation->getReservation()->getLocationId()->getValue() : null,
                    'bookingStart'       =>
                        $recurringReservation->getReservation()->getBookingStart()->getValue()->format('Y-m-d H:i:s'),
                    'bookingEnd'         =>
                        $recurringReservation->getReservation()->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                    'notifyParticipants' => $recurringReservation->getReservation()->isNotifyParticipants(),
                    'status'             => $recurringReservation->getReservation()->getStatus()->getValue(),
                    'dateTimeValues'     => [
                        [
                            'start' => $recurringReservation->getReservation()->getBookingStart()->getValue()->format('Y-m-d H:i'),
                            'end'   => $recurringReservation->getReservation()->getBookingEnd()->getValue()->format('Y-m-d H:i'),
                        ]
                    ],
                    'persons'            => $recurringReservation->getBooking()->getPersons()->getValue(),
                    'extras'             => [],
                    'duration'           => $recurringReservation->getBooking()->getDuration()
                        ? $recurringReservation->getBooking()->getDuration()->getValue() : null,
                    'utcOffset'          => $recurringReservation->getBooking()->getUtcOffset() ?
                        $recurringReservation->getBooking()->getUtcOffset()->getValue() : null,
                    'deposit'            => $recurringReservation->getApplyDeposit()->getValue(),
                    'customFields'       => $recurringReservation->getBooking()->getCustomFields() ?
                        json_decode($recurringReservation->getBooking()->getCustomFields()->getValue(), true) : null,
                ];

                $recurringBookableSettings = $recurringReservation->getBookable()->getSettings() ?
                    json_decode($recurringReservation->getBookable()->getSettings()->getValue(), true) : null;

                $recurringAppointmentData['wcProductId'] =
                    $recurringBookableSettings && isset($recurringBookableSettings['payments']['wc']['productId']) ?
                        $recurringBookableSettings['payments']['wc']['productId'] : null;

                $recurringAppointmentData['couponId'] = !$recurringReservation->getBooking()->getCoupon() ? null :
                    $recurringReservation->getBooking()->getCoupon()->getId()->getValue();

                $recurringAppointmentData['couponCode'] = !$recurringReservation->getBooking()->getCoupon() ? null :
                    $recurringReservation->getBooking()->getCoupon()->getCode()->getValue();

                $recurringAppointmentData['useCoupon'] = $recurringReservation->getBooking()->getCoupon() !== null;

                /** @var CustomerBooking $recurringBooking */
                $recurringBooking = $recurringReservation->getBooking();

                foreach ($recurringBooking->getExtras()->keys() as $extraKey) {
                    /** @var CustomerBookingExtra $bookingExtra */
                    $bookingExtra = $recurringBooking->getExtras()->getItem($extraKey);

                    $recurringAppointmentData['extras'][] = [
                        'extraId'  => $bookingExtra->getExtraId()->getValue(),
                        'quantity' => $bookingExtra->getQuantity()->getValue()
                    ];
                }

                $recurringAppointmentsData[] = $recurringAppointmentData;
            }
        }

        $info = [
            'type'               => Entities::APPOINTMENT,
            'serviceId'          => $service->getId()->getValue(),
            'providerId'         => $appointment->getProviderId()->getValue(),
            'locationId'         => $appointment->getLocationId() ? $appointment->getLocationId()->getValue() : null,
            'name'               => $service->getName()->getValue(),
            'couponId'           => $booking->getCoupon() ? $booking->getCoupon()->getId()->getValue() : '',
            'couponCode'         => $booking->getCoupon() ? $booking->getCoupon()->getCode()->getValue() : '',
            'bookingStart'       => $appointment->getBookingStart()->getValue()->format('Y-m-d H:i'),
            'bookingEnd'         => $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i'),
            'status'             => $appointment->getStatus()->getValue(),
            'dateTimeValues'     => [
                [
                    'start' => $appointment->getBookingStart()->getValue()->format('Y-m-d H:i'),
                    'end'   => $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i'),
                ]
            ],
            'allCustomFields'    => $booking->getCustomFields() && $booking->getCustomFields()->getValue() ?
                json_decode($booking->getCustomFields()->getValue(), true) : null,
            'notifyParticipants' => $appointment->isNotifyParticipants(),
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
                        'customFields'    => $customer->getCustomFields() ?
                            json_decode($customer->getCustomFields()->getValue(), true) : null,
                    ],
                    'info'         => $booking->getInfo()->getValue(),
                    'persons'      => $booking->getPersons()->getValue(),
                    'extras'       => [],
                    'status'       => $booking->getStatus()->getValue(),
                    'utcOffset'    => $booking->getUtcOffset() ? $booking->getUtcOffset()->getValue() : null,
                    'customFields' => $booking->getCustomFields() ?
                        json_decode($booking->getCustomFields()->getValue(), true) : null,
                    'deposit'      => $reservation->getApplyDeposit()->getValue(),
                    'duration'     => $booking->getDuration() ? $booking->getDuration()->getValue() : null,
                ]
            ],
            'payment'            => [
                'gateway' => $paymentGateway
            ],
            'recurring'          => $recurringAppointmentsData,
            'isCart'             => !empty($requestData['isCart']),
            'package'            => [],
            'locale'             => $reservation->getLocale()->getValue(),
            'timeZone'           => $reservation->getTimeZone()->getValue(),
        ];

        foreach ($booking->getExtras()->keys() as $extraKey) {
            /** @var CustomerBookingExtra $bookingExtra */
            $bookingExtra = $booking->getExtras()->getItem($extraKey);

            $info['bookings'][0]['extras'][] = [
                'extraId'  => $bookingExtra->getExtraId()->getValue(),
                'quantity' => $bookingExtra->getQuantity()->getValue()
            ];
        }

        return $info;
    }

    /**
     * @param array $reservation
     *
     * @return array
     */
    public function getWooCommerceDataFromArray($reservation, $index)
    {
        /** @var array $appointment */
        $appointment = $reservation['appointment'];

        /** @var array $service */
        $service = $reservation['bookable'];

        /** @var array $customer */
        $customer = $reservation['customer'];

        /** @var array $booking */
        $booking = $reservation['booking'];

        $customerInfo = !empty($booking['info']) ? json_decode($booking['info'], true) : null;

        $info = [
            'type'               => Entities::APPOINTMENT,
            'serviceId'          => $service['id'],
            'providerId'         => $appointment['providerId'],
            'locationId'         => $appointment['locationId'],
            'name'               => $service['name'],
            'couponId'           => $booking['couponId'],
            'couponCode'         => !empty($booking['coupon']) ? $booking['coupon']['code'] : null,
            'bookingStart'       => $appointment['bookingStart'],
            'bookingEnd'         => $appointment['bookingEnd'],
            'status'             => $appointment['status'],
            'dateTimeValues'     => [
                [
                    'start' => $appointment['bookingStart'],
                    'end'   => $appointment['bookingEnd'],
                ]
            ],
            'notifyParticipants' => $appointment['notifyParticipants'],
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
                    'info'         => $booking['info'],
                    'persons'      => $booking['persons'],
                    'extras'       => [],
                    'status'       => $booking['status'],
                    'utcOffset'    => $booking['utcOffset'],
                    'customFields' => !empty($booking['customFields']) ?
                        json_decode($booking['customFields'], true) : null,
                    'deposit'      => $booking['payments'][0]['status'] === PaymentStatus::PARTIALLY_PAID,
                    'duration'     => $booking['duration'],
                ]
            ],
            'payment'            => [
                'gateway' => $booking['payments'][0]['gateway']
            ],
            'recurring'          => [],
            'package'            => [],
            'locale'             => $customerInfo ? $customerInfo['locale'] : null,
            'timeZone'           => $customerInfo ? $customerInfo['timeZone'] : null,
        ];

        foreach ($booking['extras'] as $extra) {
            $info['bookings'][0]['extras'][] = [
                'extraId'  => $extra['extraId'],
                'quantity' => $extra['quantity']
            ];
        }

        return $info;
    }



    /**
     * @param CustomerBooking $booking
     * @param Appointment     $appointment
     *
     * @return void
     *
     * @throws ContainerException
     */
    public function updateWooCommerceOrder($booking, $appointment)
    {
        /** @var Payment $payment */
        foreach ($booking->getPayments()->getItems() as $payment) {
            if ($payment->getWcOrderId() && $payment->getWcOrderId()->getValue()) {
                $appointmentArrayModified = $appointment->toArray();

                $appointmentArrayModified['bookings'] = [$booking->toArray()];

                foreach ($appointmentArrayModified['bookings'] as &$booking2) {
                    if (!empty($booking2['customFields'])) {
                        $customFields = json_decode($booking2['customFields'], true);

                        $booking2['customFields'] = $customFields;
                    }
                }

                $appointmentArrayModified['dateTimeValues'] = [
                    [
                        'start' => $appointment->getBookingStart()->getValue()->format('Y-m-d H:i'),
                        'end'   => $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i'),
                    ]
                ];

                if (WooCommerceService::isEnabled()) {
                    WooCommerceService::updateItemMetaData(
                        $payment->getWcOrderId()->getValue(),
                        $payment->getWcOrderItemId() ? $payment->getWcOrderItemId()->getValue() : null,
                        $appointmentArrayModified
                    );
                }

                foreach ($appointmentArrayModified['bookings'] as &$bookingArray) {
                    if (!empty($bookingArray['customFields'])) {
                        $bookingArray['customFields'] = json_encode($bookingArray['customFields']);
                    }
                }
            }
        }
    }

    /**
     * @param int $id
     *
     * @return Appointment
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getReservationByBookingId($id)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        return $appointmentRepository->getByBookingId($id);
    }

    /**
     * @param CustomerBooking $booking
     * @param Service         $bookable
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getPaymentAmount($booking, $bookable, $invoice = false)
    {
        /** @var TaxApplicationService $taxApplicationService */
        $taxApplicationService = $this->container->get('application.tax.service');

        /** @var Tax $serviceTax */
        $serviceTax = $this->getTax($booking->getTax());

        $persons = $booking->getPersons()->getValue();

        $serviceAmount = (float)$bookable->getPrice()->getValue() * ($this->isAggregatedPrice($bookable) ? $persons : 1);

        $serviceBookingAmount = $serviceAmount;

        if ($serviceTax && !$serviceTax->getExcluded()->getValue() && ($booking->getCoupon() || $invoice)) {
            $serviceAmount = $taxApplicationService->getBasePrice($serviceBookingAmount, $serviceTax);
        }

        $serviceAmountWithoutDiscount = $serviceAmount;

        $fullDiscount = $this->getCouponDiscountAmount($booking->getCoupon(), $serviceBookingAmount);

        $serviceDiscountAmount = $this->getCouponDiscountAmount($booking->getCoupon(), $serviceAmount);

        $serviceDiscountedAmount = $serviceAmount - $serviceDiscountAmount;

        $serviceAmount = $serviceDiscountedAmount;

        $deduction = $booking->getCoupon() && $booking->getCoupon()->getDeduction()
            ? (float)$booking->getCoupon()->getDeduction()->getValue()
            : 0;

        $serviceDeductionAmount = 0;

        if ($serviceDiscountedAmount > 0 && $deduction > 0) {
            $fullDiscount += $deduction;

            $serviceDeductionAmount = min($serviceDiscountedAmount, $deduction);

            $serviceAmount = $serviceDiscountedAmount - $serviceDeductionAmount;

            $deduction = $serviceDiscountedAmount >= $deduction ? 0 : $deduction - $serviceDiscountedAmount;
        }

        $reductionAmount = [
            'deduction' => $serviceDeductionAmount,
            'discount'  => $serviceDiscountAmount,
        ];

        $bookingAmount = $serviceAmount;

        $serviceTaxAmount = 0;

        if ($serviceTax && !$serviceTax->getExcluded()->getValue() && ($booking->getCoupon() || $invoice)) {
            $serviceAmount    = $taxApplicationService->getBasePrice($serviceBookingAmount, $serviceTax);
            $serviceTaxAmount = $this->getTaxAmount(
                $serviceTax,
                $serviceAmount - $serviceDiscountAmount - $serviceDeductionAmount
            );

            $bookingAmount = $serviceAmount + $serviceTaxAmount - $serviceDiscountAmount - $serviceDeductionAmount;
        } elseif ($serviceTax && $serviceTax->getExcluded()->getValue()) {
            $serviceTaxAmount = $this->getTaxAmount($serviceTax, $serviceAmount);
            $bookingAmount   += $serviceTaxAmount;
        }

        $extras = [];
        $extrasAmountWithoutDiscount = 0;
        $extrasTaxAmount = 0;
        /** @var CustomerBookingExtra $customerBookingExtra */
        foreach ($booking->getExtras()->getItems() as $customerBookingExtra) {
            /** @var Tax $extraTax */
            $extraTax = $this->getTax($customerBookingExtra->getTax());

            /** @var Extra $extra */
            $extra = $bookable->getExtras()->getItem($customerBookingExtra->getExtraId()->getValue());

            $isExtraAggregatedPrice = $extra->getAggregatedPrice() === null
                ? $this->isAggregatedPrice($bookable)
                : $extra->getAggregatedPrice()->getValue();

            $extraAmount = (float)$extra->getPrice()->getValue() *
                ($isExtraAggregatedPrice ? $booking->getPersons()->getValue() : 1) *
                $customerBookingExtra->getQuantity()->getValue();

            $extraBookingAmount = $extraAmount;

            if ($extraTax && !$extraTax->getExcluded()->getValue() && ($booking->getCoupon() || $invoice)) {
                $extraAmount = $taxApplicationService->getBasePrice($extraBookingAmount, $extraTax);
            }

            $extraAmountWithoutDiscount = $extraAmount;

            $fullDiscount += $this->getCouponDiscountAmount($booking->getCoupon(), $extraBookingAmount);

            $extraDiscountAmount = $this->getCouponDiscountAmount($booking->getCoupon(), $extraAmount);

            $extraDiscountedAmount = $extraAmount - $extraDiscountAmount;

            $extraAmount = $extraDiscountedAmount;

            $extraDeductionAmount = 0;

            if ($extraAmount > 0 && $deduction > 0) {
                $fullDiscount += $deduction;

                $extraDeductionAmount = min($extraDiscountedAmount, $deduction);

                $extraAmount = $extraDiscountedAmount - $extraDeductionAmount;

                $deduction = $extraDiscountedAmount >= $deduction ? 0 : $deduction - $extraDiscountedAmount;
            }

            $reductionAmount['deduction'] += $extraDeductionAmount;

            $reductionAmount['discount'] += $extraDiscountAmount;

            $extraTaxAmount = 0;
            if ($extraTax && !$extraTax->getExcluded()->getValue() && ($booking->getCoupon() || $invoice)) {
                $extraAmount = $taxApplicationService->getBasePrice($extraBookingAmount, $extraTax);

                $extraTaxAmount = $this->getTaxAmount(
                    $extraTax,
                    $extraAmount - $extraDiscountAmount - $extraDeductionAmount
                );

                $bookingAmount += $extraAmount + $extraTaxAmount - $extraDiscountAmount - $extraDeductionAmount;
            } elseif ($extraTax && $extraTax->getExcluded()->getValue()) {
                $extraTaxAmount = $this->getTaxAmount($extraTax, $extraAmount);
                $bookingAmount += $extraAmount + $extraTaxAmount;
            } else {
                $bookingAmount += $extraAmount;
            }
            $extrasAmountWithoutDiscount += $extraAmountWithoutDiscount;
            $extrasTaxAmount += $extraTaxAmount;
            if ($extraTax) {
                $extras[$customerBookingExtra->getExtraId()->getValue()] =
                    [
                        'amount' =>  $this->getTaxAmount(
                            $extraTax,
                            $extraAmountWithoutDiscount
                        ),
                        'rate' => $this->getTaxRate($extraTax),
                        'excluded' => $extraTax->getExcluded()->getValue()
                    ];
            }
        }

        $bookingAmount = (float)max(round($bookingAmount, 2), 0);

        return [
            'price'      => apply_filters('amelia_modify_payment_amount', $bookingAmount, $booking),
            'deduction'  => $reductionAmount['deduction'],
            'discount'   => $reductionAmount['discount'],
            'unit_price' => (float)$bookable->getPrice()->getValue(),
            'qty'        => $this->isAggregatedPrice($bookable) ? $persons : 1,
            'subtotal'   => $serviceAmountWithoutDiscount + $extrasAmountWithoutDiscount,
            'tax'        => $serviceTax ? $this->getTaxAmount($serviceTax, $serviceAmountWithoutDiscount) : 0,
            'tax_rate'   => $serviceTax ? $this->getTaxRate($serviceTax) : '',
            'tax_type'   => $serviceTax ? $serviceTax->getType()->getValue() : '',
            'tax_excluded' => $serviceTax ? $serviceTax->getExcluded()->getValue() : false,
            'extras_tax' => $extras,
            'total_tax'  => $serviceTaxAmount + $extrasTaxAmount,
            'full_discount' => $reductionAmount['deduction'] + $reductionAmount['discount']
        ];
    }

    /**
     * @param Reservation  $reservation
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function getReservationPaymentAmount($reservation)
    {
        $paymentAmount = $this->getAppointmentPaymentAmount($reservation);

        /** @var Reservation $recurringReservation */
        foreach ($reservation->getRecurring()->getItems() as $index => $recurringReservation) {
            /** @var Service $recurringBookable */
            $recurringBookable = $recurringReservation->getBookable();

            if ($reservation->isCart()->getValue() || $index < $recurringBookable->getRecurringPayment()->getValue()) {
                $paymentAmount += $this->getAppointmentPaymentAmount($recurringReservation);
            }
        }

        return $paymentAmount;
    }

    /**
     * @param Reservation $reservation
     * @param bool        $usePayment
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getProvidersPaymentAmount($reservation, $usePayment = true)
    {
        $amountData = [];

        /** @var Payment $payment */
        $payment = $usePayment ? $reservation->getBooking()->getPayments()->getItem(0) : null;

        $amountData[$reservation->getReservation()->getProviderId()->getValue()][] = [
            'paymentId' => $payment ? $payment->getId()->getValue() : null,
            'amount'    => $this->getAppointmentPaymentAmount($reservation),
        ];

        /** @var Reservation $recurringReservation */
        foreach ($reservation->getRecurring()->getItems() as $index => $recurringReservation) {
            /** @var Service $recurringBookable */
            $recurringBookable = $recurringReservation->getBookable();

            if ($reservation->isCart()->getValue() || $index < $recurringBookable->getRecurringPayment()->getValue()) {
                /** @var Payment $recurringPayment */
                $recurringPayment = $usePayment
                    ? $recurringReservation->getBooking()->getPayments()->getItem(0)
                    : null;

                $amountData[$recurringReservation->getReservation()->getProviderId()->getValue()][] = [
                    'paymentId' => $recurringPayment ? $recurringPayment->getId()->getValue() : null,
                    'amount'    => $this->getAppointmentPaymentAmount($recurringReservation),
                ];
            }
        }

        return $amountData;
    }

    /**
     * @param Reservation $reservation
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function getAppointmentPaymentAmount($reservation)
    {
        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        /** @var Service $bookable */
        $bookable = $reservation->getBookable();

        $paymentAmount = $this->getPaymentAmount($reservation->getBooking(), $bookable)['price'];

        if ($reservation->getApplyDeposit()->getValue()) {
            $paymentAmount = $depositAS->calculateDepositAmount(
                $paymentAmount,
                $bookable,
                $reservation->getBooking()->getPersons()->getValue()
            );
        }

        return $paymentAmount;
    }

    /**
     * @param Payment $payment
     * @param boolean $fromLink
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getReservationByPayment($payment, $fromLink = false)
    {
        $result = new CommandResult();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        $recurringData = [];

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getByPaymentId($payment->getId()->getValue());

        if ($appointment->getLocationId()) {
            /** @var Location $location */
            $location = $locationRepository->getById($appointment->getLocationId()->getValue());

            $appointment->setLocation($location);
        }

        /** @var CustomerBooking $booking */
        $booking = $appointment->getBookings()->getItem($payment->getCustomerBookingId()->getValue());

        $booking->setChangedStatus(new BooleanValueObject(true));

        $this->setToken($booking);

        /** @var AbstractUser $customer */
        $customer = $customerRepository->getById($booking->getCustomerId()->getValue());

        /** @var Collection $nextPayments */
        $nextPayments = $paymentRepository->getByEntityId($payment->getId()->getValue(), 'parentId');

        /** @var Payment $nextPayment */
        foreach ($nextPayments->getItems() as $nextPayment) {
            /** @var Appointment $nextAppointment */
            $nextAppointment = $appointmentRepository->getByPaymentId($nextPayment->getId()->getValue());

            if ($nextAppointment->getLocationId()) {
                /** @var Location $location */
                $location = $locationRepository->getById($nextAppointment->getLocationId()->getValue());

                $nextAppointment->setLocation($location);
            }

            /** @var CustomerBooking $nextBooking */
            $nextBooking = $nextAppointment->getBookings()->getItem(
                $nextPayment->getCustomerBookingId()->getValue()
            );

            /** @var Service $nextService */
            $nextService = $bookableAS->getAppointmentService(
                $nextAppointment->getServiceId()->getValue(),
                $nextAppointment->getProviderId()->getValue()
            );

            $recurringData[] = [
                'type'                     => Entities::APPOINTMENT,
                Entities::APPOINTMENT      => $nextAppointment->toArray(),
                Entities::BOOKING          => $nextBooking->toArray(),
                'appointmentStatusChanged' => true,
                'utcTime'                  => $this->getBookingPeriods(
                    $nextAppointment,
                    $nextBooking,
                    $nextService
                ),
                'isRetry'                  => !$fromLink,
                'fromLink'                 => $fromLink
            ];
        }

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService(
            $appointment->getServiceId()->getValue(),
            $appointment->getProviderId()->getValue()
        );

        $customerCabinetUrl = '';

        if (
            $customer &&
            $customer->getEmail() &&
            $customer->getEmail()->getValue() &&
            $booking->getInfo() &&
            $booking->getInfo()->getValue()
        ) {
            $infoJson = json_decode($booking->getInfo()->getValue(), true);

            /** @var HelperService $helperService */
            $helperService = $this->container->get('application.helper.service');

            $customerCabinetUrl = $helperService->getCustomerCabinetUrl(
                $customer->getEmail()->getValue(),
                'email',
                $appointment->getBookingStart()->getValue()->format('Y-m-d'),
                $appointment->getBookingEnd()->getValue()->format('Y-m-d'),
                $infoJson['locale']
            );
        }

        $result->setData(
            [
                'type'                     => Entities::APPOINTMENT,
                Entities::APPOINTMENT      => $appointment->toArray(),
                Entities::BOOKING          => $booking->toArray(),
                'customer'                 => $customer->toArray(),
                'packageId'                => 0,
                'recurring'                => $recurringData,
                'appointmentStatusChanged' => true,
                'bookable'                 => $service->toArray(),
                'utcTime'                  => $this->getBookingPeriods(
                    $appointment,
                    $booking,
                    $service
                ),
                'isRetry'                  => !$fromLink,
                'paymentId'                => $payment->getId()->getValue(),
                'packageCustomerId'        => null,
                'payment'                  => [
                    'id'           => $payment->getId()->getValue(),
                    'amount'       => $payment->getAmount()->getValue(),
                    'status'       => $payment->getStatus()->getValue(),
                    'gateway'      => $payment->getGateway()->getName()->getValue(),
                    'gatewayTitle' => $payment->getGatewayTitle() ? $payment->getGatewayTitle()->getValue() : '',
                ],
                'customerCabinetUrl'       => $customerCabinetUrl,
                'fromLink'                 => $fromLink
            ]
        );

        return $result;
    }

    /**
     * @param int $bookingId
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getBookingResultByBookingId($bookingId)
    {
        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        $recurringData = [];

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getByBookingId($bookingId);

        if ($appointment->getLocationId()) {
            /** @var Location $location */
            $location = $locationRepository->getById($appointment->getLocationId()->getValue());

            $appointment->setLocation($location);
        }

        /** @var CustomerBooking $booking */
        $booking = $appointment->getBookings()->getItem($bookingId);

        $booking->setChangedStatus(new BooleanValueObject(true));

        $this->setToken($booking);

        /** @var AbstractUser $customer */
        $customer = $customerRepository->getById($booking->getCustomerId()->getValue());

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService(
            $appointment->getServiceId()->getValue(),
            $appointment->getProviderId()->getValue()
        );

        $customerCabinetUrl = '';

        if (
            $customer &&
            $customer->getEmail() &&
            $customer->getEmail()->getValue() &&
            $booking->getInfo() &&
            $booking->getInfo()->getValue()
        ) {
            $infoJson = json_decode($booking->getInfo()->getValue(), true);

            /** @var HelperService $helperService */
            $helperService = $this->container->get('application.helper.service');

            $customerCabinetUrl = $helperService->getCustomerCabinetUrl(
                $customer->getEmail()->getValue(),
                'email',
                $appointment->getBookingStart()->getValue()->format('Y-m-d'),
                $appointment->getBookingEnd()->getValue()->format('Y-m-d'),
                $infoJson['locale']
            );
        }

        $result->setData(
            [
                'type'                     => Entities::APPOINTMENT,
                Entities::APPOINTMENT      => $appointment->toArray(),
                Entities::BOOKING          => $booking->toArray(),
                'customer'                 => $customer->toArray(),
                'packageId'                => 0,
                'recurring'                => $recurringData,
                'appointmentStatusChanged' => true,
                'bookable'                 => $service->toArray(),
                'utcTime'                  => $this->getBookingPeriods(
                    $appointment,
                    $booking,
                    $service
                ),
                'isRetry'                  => true,
                'paymentId'                => null,
                'packageCustomerId'        => null,
                'payment'                  => null,
                'customerCabinetUrl'       => $customerCabinetUrl,
            ]
        );

        return $result;
    }

    /**
     * @param Service $service
     * @param int $customerId
     * @param DateTime $appointmentStart
     * @param int $bookingId
     *
     * @return bool
     * @throws Exception
     */
    public function checkLimitsPerCustomer($service, $customerId, $appointmentStart, $bookingId = null)
    {
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $limitPerCustomerGlobal = $settingsDS->getSetting('roles', 'limitPerCustomerService');

        if (!empty($limitPerCustomerGlobal) || !empty($service->getLimitPerCustomer())) {
            $limitService = !empty($service->getLimitPerCustomer()) ?
                json_decode($service->getLimitPerCustomer()->getValue(), true) : null;

            $optionEnabled = empty($limitService) ? $limitPerCustomerGlobal['enabled'] : $limitService['enabled'];

            if ($optionEnabled) {
                /** @var AppointmentRepository $appointmentRepository */
                $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

                $serviceSpecific =
                    !empty($limitService['timeFrame']) ||
                    !empty($limitService['period']) ||
                    !empty($limitService['from']) ||
                    !empty($limitService['numberOfApp']);

                $limitPerCustomer = !empty($limitService) ? [
                    'numberOfApp' => !empty($limitService['numberOfApp']) ?
                        $limitService['numberOfApp'] : $limitPerCustomerGlobal['numberOfApp'],
                    'timeFrame'   => !empty($limitService['timeFrame']) ?
                        $limitService['timeFrame'] : $limitPerCustomerGlobal['timeFrame'],
                    'period'      => !empty($limitService['period']) ?
                        $limitService['period'] : $limitPerCustomerGlobal['period'],
                    'from'        => !empty($limitService['from']) ?
                        $limitService['from'] : $limitPerCustomerGlobal['from']
                ] : $limitPerCustomerGlobal;

                $count = $appointmentRepository->getRelevantAppointmentsCount(
                    $service,
                    $customerId,
                    $appointmentStart,
                    $limitPerCustomer,
                    $serviceSpecific,
                    $bookingId
                );

                if ($count >= $limitPerCustomer['numberOfApp']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array      $bookingCustomFieldsArray
     * @param Collection $customFieldsCollection
     * @param int        $serviceId
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getCustomFieldsJsonForService(
        $bookingCustomFieldsArray,
        $customFieldsCollection,
        $serviceId
    ) {
        foreach ($bookingCustomFieldsArray as $customFieldId => $value) {
            /** @var CustomField $customField */
            $customField = $customFieldsCollection->getItem($customFieldId);

            $isCustomFieldForService = $customField->getAllServices() && $customField->getAllServices()->getValue();

            /** @var Service $customFieldService */
            foreach ($customField->getServices()->getItems() as $customFieldService) {
                if ($customFieldService->getId()->getValue() === (int)$serviceId) {
                    $isCustomFieldForService = true;
                    break;
                }
            }

            if (!$isCustomFieldForService) {
                unset($bookingCustomFieldsArray[$customFieldId]);
            }
        }

        return json_encode($bookingCustomFieldsArray);
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
            /** @var Collection $taxes */
            $taxes = $taxAS->getAll();

            foreach ($data['bookings'] as $bookingKey => $booking) {
                if (empty($data['bookings'][$bookingKey]['packageCustomerService'])) {
                    $data['bookings'][$bookingKey]['tax'] = $taxAS->getTaxData(
                        $data['serviceId'],
                        Entities::SERVICE,
                        $taxes
                    );

                    if (!empty($data['bookings'][$bookingKey]['extras'])) {
                        foreach ($data['bookings'][$bookingKey]['extras'] as $extraKey => $bookingData) {
                            $data['bookings'][$bookingKey]['extras'][$extraKey]['tax'] = $taxAS->getTaxData(
                                $bookingData['extraId'],
                                Entities::EXTRA,
                                $taxes
                            );
                        }
                    }
                }
            }

            foreach (!empty($data['recurring']) ? $data['recurring'] : [] as $key => $recurringData) {
                if (empty($recurringData['bookings'][0]['packageCustomerService'])) {
                    $data['recurring'][$key]['bookings'][0]['tax'] = $taxAS->getTaxData(
                        !empty($recurringData['serviceId']) ? $recurringData['serviceId'] : $data['serviceId'],
                        Entities::SERVICE,
                        $taxes
                    );

                    if (!empty($recurringData['bookings'][0]['extras'])) {
                        foreach ($recurringData['bookings'][0]['extras'] as $extraKey => $bookingData) {
                            $data['recurring'][$key]['bookings'][0]['extras'][$extraKey]['tax'] = $taxAS->getTaxData(
                                $bookingData['extraId'],
                                Entities::EXTRA,
                                $taxes
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $bookingId
     * @param string $token
     *
     * @return array
     *
     * @throws AccessDeniedException
     */
    public function deleteBooking($bookingId, $token = null)
    {
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $this->container->get('application.booking.booking.service');

        /** @var AppointmentApplicationService $appointmentApplicationService */
        $appointmentApplicationService = $this->container->get('application.booking.appointment.service');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var CustomerBooking $customerBooking */
        $customerBooking = $customerBookingRepository->getById((int)$bookingId);

        if (!$customerBooking) {
            throw new \Exception('Booking not found');
        }

        if ($token && (!$customerBooking->getToken() || $customerBooking->getToken()->getValue() !== $token)) {
            throw new AccessDeniedException('Invalid token');
        }

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getByBookingId($bookingId);

        /** @var CustomerBooking $removedBooking */
        $removedBooking = $appointment->getBookings()->getItem($bookingId);

        $appointmentRepository->beginTransaction();

        $hasMultipleBookings = $appointment->getBookings()->length() > 1;

        if (!$token) {
            // Called from DeleteBookingCommandHandler, deleting package booking on the backend
            do_action(
                'amelia_before_package_booking_deleted',
                $appointment ? $appointment->toArray() : null,
                $removedBooking ? $removedBooking->toArray() : null
            );
        }

        if ($appointment->getBookings()->length() === 1) {
            $resultData = $appointmentApplicationService->removeBookingFromNonGroupAppointment(
                $appointment,
                $removedBooking
            );
        } else {
            $resultData = $appointmentApplicationService->removeBookingFromGroupAppointment(
                $appointment,
                $removedBooking
            );
        }

        $isSuccess = true;

        if ($hasMultipleBookings) {
            if (!$bookingApplicationService->delete($removedBooking)) {
                $isSuccess = false;
            }
        } elseif (!$appointmentApplicationService->delete($appointment)) {
            $isSuccess = false;
        }

        if (!$isSuccess) {
            $appointmentRepository->rollback();
            throw new \Exception('Could not delete booking');
        }

        $appointmentRepository->commit();

        if (!$token) {
            // Called from DeleteBookingCommandHandler, deleting package booking on the backend
            do_action(
                'amelia_after_package_booking_deleted',
                $appointment ? $appointment->toArray() : null,
                $removedBooking ? $removedBooking->toArray() : null
            );
        }

        return $resultData;
    }
}
