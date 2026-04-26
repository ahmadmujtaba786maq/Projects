<?php

namespace AmeliaBooking\Application\Services\Booking;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Bookable\AbstractPackageApplicationService;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\Deposit\AbstractDepositApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService as ApplicationTimeSlotService;
use AmeliaBooking\Application\Services\Zoom\AbstractZoomApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\PackageCustomerService;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\Booking\AppointmentDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Interval\IntervalService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\Services\User\ProviderService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\PackageCustomerServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\EventListeners\Booking\Appointment\AppointmentStatusUpdatedEventHandler;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use DateTime;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;
use AmeliaBooking\Infrastructure\Licence;

/**
 * Class AppointmentApplicationService
 *
 * @package AmeliaBooking\Application\Services\Booking
 */
class AppointmentApplicationService
{
    private $container;

    /**
     * AppointmentApplicationService constructor.
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
     * @param array $data
     *
     * @return void
     * @throws Exception
     */
    public function convertTime(&$data)
    {
        if (!empty($data['utc'])) {
            $data['bookingStart'] = DateTimeService::getCustomDateTimeFromUtc(
                $data['bookingStart']
            );
        } elseif (!empty($data['timeZone'])) {
            $data['bookingStart'] = DateTimeService::getDateTimeObjectInTimeZone(
                $data['bookingStart'],
                $data['timeZone']
            )->setTimezone(DateTimeService::getTimeZone())->format('Y-m-d H:i:s');
        }
    }

    /**
     * @param array   $data
     * @param Service $service
     *
     * @return Appointment
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function build($data, $service)
    {
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        $data['bookingEnd'] = $data['bookingStart'];

        /** @var Appointment $appointment */
        $appointment = AppointmentFactory::create($data);

        $includedExtrasIds = [];

        /** @var Provider $provider */
        $provider = $this->isPeriodCustomPricing($service)
            ? $userRepository->getById($data['providerId'])
            : null;

        /** @var CustomerBooking $customerBooking */
        foreach ($appointment->getBookings()->getItems() as $customerBooking) {
            /** @var CustomerBookingExtra $customerBookingExtra */
            foreach ($customerBooking->getExtras()->getItems() as $customerBookingExtra) {
                $extraId = $customerBookingExtra->getExtraId()->getValue();

                /** @var Extra $extra */
                $extra = $service->getExtras()->getItem($extraId);

                if (!in_array($extraId, $includedExtrasIds, true)) {
                    $includedExtrasIds[] = $extraId;
                }

                $customerBookingExtra->setPrice(new Price($extra->getPrice()->getValue()));
                $customerBookingExtra->setAggregatedPrice(
                    new BooleanValueObject($extra->getAggregatedPrice() ?
                        $extra->getAggregatedPrice()->getValue() :
                        $service->getAggregatedPrice()->getValue())
                );
            }

            $customerBooking->setPrice(
                new Price(
                    $this->getBookingPriceForService(
                        $service,
                        $customerBooking,
                        $provider,
                        $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                    )
                )
            );
        }

        // Set appointment status based on booking statuses
        $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment($service, $bookingsCount);
        $appointment->setStatus(new BookingStatus($appointmentStatus));

        $this->calculateAndSetAppointmentEnd($appointment, $service);

        return $appointment;
    }

    /**
     * @param array   $appointmentData
     * @param bool    $isFrontEndBooking
     * @param Service $service
     *
     * @return Appointment|null
     * @throws QueryExecutionException
     */
    public function getAlreadyBookedAppointment($appointmentData, $isFrontEndBooking, $service)
    {
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        $bookIfPending = $isFrontEndBooking && $settingsDS->getSetting('appointments', 'allowBookingIfPending');

        $personsCount = 0;

        foreach ($appointmentData['bookings'] as $bookingData) {
            $personsCount += $bookingData['persons'];
        }

        /** @var Collection $existingAppointments */
        $existingAppointments = $appointmentRepo->getFiltered(
            [
                'dates'         => [$appointmentData['bookingStart'], $appointmentData['bookingStart']],
                'services'      => [$appointmentData['serviceId']],
                'providers'     => [$appointmentData['providerId']],
                'skipServices'  => true,
                'skipProviders' => true,
                'skipCustomers' => true,
            ]
        );

        if ($existingAppointments->length()) {
            /** @var Appointment $existingAppointment */
            foreach ($existingAppointments->getItems() as $existingAppointment) {
                $persons = 0;

                /** @var CustomerBooking $booking */
                foreach ($existingAppointment->getBookings()->getItems() as $booking) {
                    $persons += $bookingAS->isBookingApprovedOrPending($booking->getStatus()->getValue())
                        ? $booking->getPersons()->getValue()
                        : 0;
                }

                $status = $existingAppointment->getStatus()->getValue();

                $hasLocation = true;

                if (
                    !empty($appointmentData['locationId']) &&
                    $existingAppointment->getLocationId() &&
                    $existingAppointment->getLocationId()->getValue() !== (int)$appointmentData['locationId']
                ) {
                    $hasLocation = false;
                }

                $hasCapacity =
                    ($persons + $personsCount) <= $service->getMaxCapacity()->getValue() &&
                    !($existingAppointment->isFull() ? $existingAppointment->isFull()->getValue() : false);

                if (
                    ($status === BookingStatus::APPROVED && $hasCapacity && $hasLocation) ||
                    ($status === BookingStatus::PENDING && ($bookIfPending || $hasCapacity) && $hasLocation) ||
                    ($status === BookingStatus::CANCELED || $status === BookingStatus::REJECTED || $status === BookingStatus::NO_SHOW)
                ) {
                    return $existingAppointment;
                }
            }
        }

        return null;
    }

    /**
     * @param Appointment $appointment
     * @param Appointment $oldAppointment
     * @param Service     $service
     * @param array       $appointmentData
     * @param array       $paymentData
     *
     * @return void
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws CustomerBookedException
     * @throws BookingUnavailableException
     * @throws NotFoundException
     */
    public function addOrEditAppointment(
        $appointment,
        $oldAppointment,
        $service,
        $appointmentData,
        $paymentData
    ) {
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get('domain.users.repository');

        /** @var Provider $provider */
        $provider = $this->isPeriodCustomPricing($service)
            ? $userRepository->getById($appointment->getProviderId()->getValue())
            : null;

        $appointmentStatusChanged = false;

        if ($oldAppointment !== null) {
            /** @var AppointmentDomainService $appointmentDS */
            $appointmentDS = $this->container->get('domain.booking.appointment.service');

            if (!empty($appointmentData['locationId'])) {
                $resetLocation = true;

                /** @var CustomerBooking $booking */
                foreach ($oldAppointment->getBookings()->getItems() as $booking) {
                    if ($bookingAS->isBookingApprovedOrPending($booking->getStatus()->getValue())) {
                        $resetLocation = false;

                        break;
                    }
                }

                if ($resetLocation) {
                    $appointment->setLocationId(new Id($appointmentData['locationId']));
                }
            }

            foreach ($appointmentData['bookings'] as $bookingArray) {
                /** @var CustomerBooking $newBooking */
                $newBooking = CustomerBookingFactory::create($bookingArray);

                /** @var CustomerBooking $booking */
                foreach ($appointment->getBookings()->getItems() as $booking) {
                    if ($booking->getCustomerId()->getValue() === $newBooking->getCustomerId()->getValue()) {
                        throw new CustomerBookedException(FrontendStrings::getCommonStrings()['customer_already_booked_app']);
                    }
                }

                $newBooking->setChangedStatus(new BooleanValueObject(true));

                $newBooking->setAppointmentId($oldAppointment->getId());

                $newBooking->setPrice(
                    new Price(
                        $this->getBookingPriceForService(
                            $service,
                            $newBooking,
                            $provider,
                            $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                        )
                    )
                );

                $newBooking->setAggregatedPrice($service->getAggregatedPrice());

                /** @var CustomerBookingExtra $bookingExtra */
                foreach ($newBooking->getExtras()->getItems() as $bookingExtra) {
                    /** @var Extra $selectedExtra */
                    $selectedExtra = $service->getExtras()->getItem($bookingExtra->getExtraId()->getValue());

                    $bookingExtra->setPrice($selectedExtra->getPrice());
                }

                $maximumDuration = $this->getMaximumBookingDuration($appointment, $service);

                if ($newBooking->getDuration() && $newBooking->getDuration()->getValue() > $maximumDuration) {
                    $service->setDuration(new PositiveDuration($maximumDuration));
                }

                $appointment->getBookings()->addItem($newBooking);
            }

            $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

            $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment($service, $bookingsCount);

            $appointment->setStatus(new BookingStatus($appointmentStatus));

            $appointmentStatusChanged =
                $appointment->getStatus()->getValue() !== BookingStatus::CANCELED &&
                $appointment->getStatus()->getValue() !== BookingStatus::REJECTED &&
                $this->isAppointmentStatusChanged($appointment, $oldAppointment);

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                $booking->setChangedStatus(
                    new BooleanValueObject(
                        (
                            $appointmentStatusChanged &&
                            $booking->getId() &&
                            $booking->getId()->getValue() &&
                            $booking->getStatus()->getValue() === BookingStatus::APPROVED &&
                            $appointment->getStatus()->getValue() === BookingStatus::APPROVED
                        ) || (
                            !$booking->getId() ||
                            !$booking->getId()->getValue()
                        )
                    )
                );
            }

            $this->calculateAndSetAppointmentEnd($appointment, $service);
        } else {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                $booking->setChangedStatus(new BooleanValueObject(true));
            }
        }

        $appointment->setChangedStatus(new BooleanValueObject($appointmentStatusChanged));

        $personsCount = 0;

        $selectedExtras = [];

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            $personsCount +=
                (!$booking->getId() || !$booking->getId()->getValue()) &&
                $bookingAS->isBookingApprovedOrPending($booking->getStatus()->getValue())
                    ? $booking->getPersons()->getValue()
                    : 0;

            /** @var CustomerBookingExtra $customerBookingExtra */
            foreach ($booking->getExtras()->getItems() as $customerBookingExtra) {
                $selectedExtras[] = [
                    'id'       => $customerBookingExtra->getExtraId()->getValue(),
                    'quantity' => $customerBookingExtra->getQuantity()->getValue(),
                ];
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
        }

        /** @var ApplicationTimeSlotService $applicationTimeSlotService */
        $applicationTimeSlotService = $this->container->get('application.timeSlot.service');

        if (
            !$applicationTimeSlotService->isSlotFree(
                $service,
                $appointment->getBookingStart()->getValue(),
                $appointment->getBookingStart()->getValue(),
                $appointment->getBookingStart()->getValue(),
                $appointment->getProviderId()->getValue(),
                $appointment->getLocationId() ? $appointment->getLocationId()->getValue() : null,
                $selectedExtras,
                null,
                $personsCount,
                false
            )
        ) {
            throw new BookingUnavailableException(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
        }

        if ($oldAppointment === null) {
            $this->add($appointment, $service, $paymentData, true);
        } else {
            $this->update(
                $oldAppointment,
                $appointment,
                new Collection(),
                $service,
                $paymentData
            );
        }
    }

    /**
     * @param Appointment $appointment
     * @param Service     $service
     * @param array       $paymentData
     * @param bool        $isBackendBooking
     *
     * @return Appointment
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function add($appointment, $service, $paymentData, $isBackendBooking)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);
        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        $appointmentId = $appointmentRepository->add($appointment);
        $appointment->setId(new Id($appointmentId));

        foreach ($appointment->getBookings()->keys() as $customerBookingKey) {
            /** @var CustomerBooking $customerBooking */
            $customerBooking = $appointment->getBookings()->getItem($customerBookingKey);

            $customerBooking->setAppointmentId($appointment->getId());
            $customerBooking->setAggregatedPrice(new BooleanValueObject($service->getAggregatedPrice()->getValue()));
            $customerBooking->setToken(new Token());
            $customerBooking->setActionsCompleted(new BooleanValueObject($isBackendBooking));
            $customerBooking->setCreated(new DateTimeValue(DateTimeService::getNowDateTimeObject()));

            $customerBookingId = $bookingRepository->add($customerBooking);

            foreach ($customerBooking->getExtras()->keys() as $cbExtraKey) {
                /** @var CustomerBookingExtra $customerBookingExtra */
                $customerBookingExtra = $customerBooking->getExtras()->getItem($cbExtraKey);

                /** @var Extra $serviceExtra */
                $serviceExtra = $service->getExtras()->getItem($customerBookingExtra->getExtraId()->getValue());

                $customerBookingExtra->setAggregatedPrice(
                    new BooleanValueObject(
                        $reservationService->isExtraAggregatedPrice(
                            $serviceExtra->getAggregatedPrice(),
                            $service->getAggregatedPrice()
                        )
                    )
                );

                $customerBookingExtra->setCustomerBookingId(new Id($customerBookingId));
                $customerBookingExtraId = $customerBookingExtraRepository->add($customerBookingExtra);
                $customerBookingExtra->setId(new Id($customerBookingExtraId));
            }

            $customerBooking->setId(new Id($customerBookingId));

            if ($paymentData) {
                $paymentAmount = $reservationService->getPaymentAmount($customerBooking, $service)['price'];

                if (
                    $customerBooking->getDeposit() &&
                    $customerBooking->getDeposit()->getValue() &&
                    $paymentData['gateway'] !== PaymentType::ON_SITE
                ) {
                    $paymentDeposit = $depositAS->calculateDepositAmount(
                        $paymentAmount,
                        $service,
                        $customerBooking->getPersons()->getValue()
                    );

                    $paymentData['deposit'] = $paymentAmount !== $paymentDeposit;

                    $paymentAmount = $paymentDeposit;
                }

                if (
                    $customerBooking->getCustomerId() &&
                    !empty($paymentData['customerPaymentParentId'][$customerBooking->getCustomerId()->getValue()])
                ) {
                    $paymentData['parentId'] =
                        $paymentData['customerPaymentParentId'][$customerBooking->getCustomerId()->getValue()];
                }

                /** @var Payment $payment */
                $payment = $reservationService->addPayment(
                    !$customerBooking->getPackageCustomerService() ?
                        $customerBooking->getId()->getValue() : null,
                    $customerBooking->getPackageCustomerService() ?
                        $customerBooking->getPackageCustomerService()->getPackageCustomer()->getId()->getValue() : null,
                    $paymentData,
                    $paymentAmount,
                    $appointment->getBookingStart()->getValue(),
                    Entities::APPOINTMENT
                );

                /** @var Collection $payments */
                $payments = new Collection();

                $payments->addItem($payment);

                $customerBooking->setPayments($payments);
            }
        }

        return $appointment;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Appointment $oldAppointment
     * @param Appointment $newAppointment
     * @param Collection  $removedBookings
     * @param Service     $service
     * @param array       $paymentData
     *
     * @return bool
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function update($oldAppointment, $newAppointment, $removedBookings, $service, $paymentData)
    {
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');
        /** @var AbstractDepositApplicationService $depositAS */
        $depositAS = $this->container->get('application.deposit.service');

        $appointmentRepo->update($oldAppointment->getId()->getValue(), $newAppointment);

        /** @var CustomerBooking $newBooking */
        foreach ($newAppointment->getBookings()->getItems() as $newBooking) {
            // Update Booking if ID exist
            if ($newBooking->getId() && $newBooking->getId()->getValue()) {
                $bookingRepository->update($newBooking->getId()->getValue(), $newBooking);

                if ($oldAppointment->getServiceId()->getValue() !== $newAppointment->getServiceId()->getValue()) {
                    $bookingRepository->updatePrice($newBooking->getId()->getValue(), $newBooking);

                    $bookingRepository->updateTax($newBooking->getId()->getValue(), $newBooking);
                }

                if ($oldAppointment->getBookings()->keyExists($newBooking->getId()->getValue())) {
                    /** @var CustomerBooking $oldBooking */
                    $oldBooking = $oldAppointment->getBookings()->getItem($newBooking->getId()->getValue());

                    if ($this->isDurationPricingType($service) &&
                        $newBooking->getDuration() &&
                        $newBooking->getDuration()->getValue() !== (
                            $oldBooking->getDuration()
                                ? $oldBooking->getDuration()->getValue()
                                : $service->getDuration()->getValue()
                        )
                    ) {
                        $bookingRepository->updatePrice($newBooking->getId()->getValue(), $newBooking);
                    }

                    if ($this->isPersonPricingType($service) &&
                        $newBooking->getPersons()->getValue() !== $oldBooking->getPersons()->getValue()
                    ) {
                        $bookingRepository->updatePrice($newBooking->getId()->getValue(), $newBooking);
                    }
                }
            }

            // Add Booking if ID does not exist
            if ($newBooking->getId() === null || ($newBooking->getId()->getValue() === 0)) {
                $newBooking->setAppointmentId($newAppointment->getId());
                $newBooking->setToken(new Token());
                $newBooking->setAggregatedPrice(new BooleanValueObject($service->getAggregatedPrice()->getValue()));
                $newBooking->setActionsCompleted(new BooleanValueObject(!empty($paymentData['isBackendBooking'])));
                $newBookingId = $bookingRepository->add($newBooking);

                $newBooking->setId(new Id($newBookingId));

                if ($paymentData) {
                    $paymentAmount = $reservationService->getPaymentAmount($newBooking, $service)['price'];

                    if (
                        $newBooking->getDeposit() &&
                        $newBooking->getDeposit()->getValue() &&
                        $paymentData['gateway'] !== PaymentType::ON_SITE
                    ) {
                        $paymentDeposit = $depositAS->calculateDepositAmount(
                            $paymentAmount,
                            $service,
                            $newBooking->getPersons()->getValue()
                        );

                        $paymentData['deposit'] = $paymentAmount !== $paymentDeposit;

                        $paymentAmount = $paymentDeposit;
                    }

                    if (
                        $newBooking->getCustomerId() &&
                        !empty($paymentData['customerPaymentParentId'][$newBooking->getCustomerId()->getValue()])
                    ) {
                        $paymentData['parentId'] =
                            $paymentData['customerPaymentParentId'][$newBooking->getCustomerId()->getValue()];
                    }

                    /** @var Payment $payment */
                    $payment = $reservationService->addPayment(
                        !$newBooking->getPackageCustomerService() ?
                            $newBooking->getId()->getValue() : null,
                        $newBooking->getPackageCustomerService() ?
                            $newBooking->getPackageCustomerService()->getPackageCustomer()->getId()->getValue() : null,
                        $paymentData,
                        $paymentAmount,
                        $newAppointment->getBookingStart()->getValue(),
                        Entities::APPOINTMENT
                    );

                    /** @var Collection $payments */
                    $payments = new Collection();

                    $payments->addItem($payment);

                    $newBooking->setPayments($payments);
                }
            }

            $newExtrasIds = [];

            /** @var CustomerBookingExtra $newExtra */
            foreach ($newBooking->getExtras()->getItems() as $newExtra) {
                // Update Extra if ID exist
                /** @var CustomerBookingExtra $newExtra */
                if ($newExtra->getId() && $newExtra->getId()->getValue()) {
                    $customerBookingExtraRepository->update($newExtra->getId()->getValue(), $newExtra);
                }

                // Add Extra if ID does not exist
                if ($newExtra->getId() === null || ($newExtra->getId()->getValue() === 0)) {
                    /** @var Extra $serviceExtra */
                    $serviceExtra = $service->getExtras()->getItem($newExtra->getExtraId()->getValue());

                    $newExtra->setAggregatedPrice(
                        new BooleanValueObject(
                            $reservationService->isExtraAggregatedPrice(
                                $serviceExtra->getAggregatedPrice(),
                                $service->getAggregatedPrice()
                            )
                        )
                    );

                    $newExtra->setCustomerBookingId($newBooking->getId());
                    $newExtraId = $customerBookingExtraRepository->add($newExtra);

                    $newExtra->setId(new Id($newExtraId));
                }

                $newExtrasIds[] = $newExtra->getId()->getValue();
            }

            if ($oldAppointment->getBookings()->keyExists($newBooking->getId()->getValue())) {
                /** @var CustomerBooking $oldBooking */
                $oldBooking = $oldAppointment->getBookings()->getItem($newBooking->getId()->getValue());

                /** @var CustomerBookingExtra $oldExtra */
                foreach ($oldBooking->getExtras()->getItems() as $oldExtra) {
                    if (!in_array($oldExtra->getId()->getValue(), $newExtrasIds)) {
                        $customerBookingExtraRepository->delete($oldExtra->getId()->getValue());
                    }
                }
            }
        }

        /** @var CustomerBooking $removedBooking */
        foreach ($removedBookings->getItems() as $removedBooking) {
            $customerBookingExtraRepository->deleteByEntityId(
                $removedBooking->getId()->getValue(),
                'customerBookingId'
            );

            $paymentRepository->deleteByEntityId(
                $removedBooking->getId()->getValue(),
                'customerBookingId'
            );

            $bookingRepository->delete($removedBooking->getId()->getValue());
        }

        return true;
    }

    /**
     * @param Appointment $appointment
     * @param array       $ignoredIds
     *
     * @return boolean
     *
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function delete($appointment, $ignoredIds = [])
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $this->container->get('application.booking.booking.service');

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if (
                $appointment->getId() &&
                $appointment->getId()->getValue() &&
                $booking->getId() &&
                $booking->getId()->getValue() &&
                empty($ignoredIds[$appointment->getId()->getValue()]['bookingsIds'][$booking->getId()->getValue()]) &&
                !$bookingApplicationService->delete($booking)
            ) {
                return false;
            }
        }

        if (
            $appointment->getId() &&
            $appointment->getId()->getValue() &&
            empty($ignoredIds[$appointment->getId()->getValue()]) &&
            !$appointmentRepository->delete($appointment->getId()->getValue())
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param Appointment $appointment
     * @param Appointment $oldAppointment
     *
     * @return bool
     */
    public function isAppointmentStatusChanged($appointment, $oldAppointment)
    {
        return $appointment->getStatus()->getValue() !== $oldAppointment->getStatus()->getValue();
    }

    /**
     * @param Appointment $appointment
     * @param Appointment $oldAppointment
     *
     * @return bool
     */
    public function isAppointmentRescheduled($appointment, $oldAppointment)
    {
        $start = $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        $end = $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        $oldStart = $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        $oldEnd = $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        return $start !== $oldStart || $end !== $oldEnd;
    }

    /**
     * @param Appointment $appointment
     * @param int         $bookingId
     *
     * @return CustomerBooking
     */
    public function getAppointmentBooking($appointment, $bookingId)
    {
        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if ($booking->getId()->getValue() === $bookingId) {
                return $booking;
            }
        }

        return null;
    }

    /**
     * Return required time for the appointment in seconds
     * and extras.
     *
     * @param Appointment $appointment
     * @param Service     $service
     *
     * @return mixed
     */
    public function getAppointmentLengthTime($appointment, $service)
    {
        $requiredTime = 0;

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            $bookingDuration = $this->getBookingLengthTime($booking, $service);

            if (
                $bookingDuration > $requiredTime &&
                (
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $booking->getStatus()->getValue() === BookingStatus::PENDING
                )
            ) {
                $requiredTime = $bookingDuration;
            }
        }

        return $requiredTime;
    }

    /**
     * Return required time for the booking in seconds
     * and extras.
     *
     * @param CustomerBooking $booking
     * @param Service     $service
     *
     * @return mixed
     */
    public function getBookingLengthTime($booking, $service)
    {
        $duration = $booking->getDuration() && $booking->getDuration()->getValue()
            ? $booking->getDuration()->getValue() : $service->getDuration()->getValue();

        /** @var CustomerBookingExtra $bookingExtra */
        foreach ($booking->getExtras()->getItems() as $bookingExtra) {
            /** @var Extra $extra */
            foreach ($service->getExtras()->getItems() as $extra) {
                if ($extra->getId()->getValue() === $bookingExtra->getExtraId()->getValue()) {
                    $extraDuration = $extra->getDuration() ? $extra->getDuration()->getValue() : 0;

                    $duration += $extraDuration * $bookingExtra->getQuantity()->getValue();
                }
            }
        }

        return $duration;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Appointment   $appointment
     * @param boolean       $isCustomer
     * @param DateTime|null $minimumAppointmentDateTime
     * @param DateTime|null $maximumAppointmentDateTime
     *
     * @return boolean
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws Exception
     * @throws ContainerException
     */
    public function canBeBooked($appointment, $isCustomer, $minimumAppointmentDateTime, $maximumAppointmentDateTime)
    {
        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        /** @var ApplicationTimeSlotService $applicationTimeSlotService */
        $applicationTimeSlotService = $this->container->get('application.timeSlot.service');

        $selectedExtras = [];

        foreach ($appointment->getBookings()->keys() as $bookingKey) {
            /** @var CustomerBooking $booking */
            $booking = $appointment->getBookings()->getItem($bookingKey);

            foreach ($booking->getExtras()->keys() as $extraKey) {
                $selectedExtras[] = [
                    'id'       => $booking->getExtras()->getItem($extraKey)->getExtraId()->getValue(),
                    'quantity' => $booking->getExtras()->getItem($extraKey)->getQuantity()->getValue(),
                ];
            }
        }

        /** @var Service $service */
        $service = $serviceRepository->getByIdWithExtras($appointment->getServiceId()->getValue());

        $maximumDuration = $this->getMaximumBookingDuration($appointment, $service);

        $service->setDuration(new PositiveDuration($maximumDuration));

        return $applicationTimeSlotService->isSlotFree(
            $service,
            $appointment->getBookingStart()->getValue(),
            $minimumAppointmentDateTime ?: $appointment->getBookingStart()->getValue(),
            $maximumAppointmentDateTime ?: $appointment->getBookingStart()->getValue(),
            $appointment->getProviderId()->getValue(),
            $appointment->getLocationId() ? $appointment->getLocationId()->getValue() : null,
            $selectedExtras,
            $appointment->getId() ? $appointment->getId()->getValue() : null,
            null,
            $isCustomer
        );
    }

    /**
     * @param int $appointmentId
     *
     * @return void
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function manageDeletionParentRecurringAppointment($appointmentId)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Collection $recurringAppointments */
        $recurringAppointments = $appointmentRepository->getFiltered(['parentId' => $appointmentId]);

        $isFirstRecurringAppointment = true;

        $newParentId = null;

        /** @var Appointment $recurringAppointment */
        foreach ($recurringAppointments->getItems() as $key => $recurringAppointment) {
            if ($isFirstRecurringAppointment) {
                $newParentId = $recurringAppointment->getId()->getValue();
            }

            $appointmentRepository->updateFieldById(
                $recurringAppointment->getId()->getValue(),
                $isFirstRecurringAppointment ? null : $newParentId,
                'parentId'
            );

            $isFirstRecurringAppointment = false;
        }
    }

    /**
     * @param string     $searchString
     *
     * @return array
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws Exception
     */
    public function getAppointmentEntitiesIdsBySearchString($searchString)
    {
        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');

        $customersArray = $customerRepository->getFiltered(
            [
                'ignoredBookings' => true,
                'search'          => $searchString,
            ],
            null
        );

        $result = [
            'customers' => array_column($customersArray, 'id'),
            'providers' => [],
            'services'  => [],
        ];

        /** @var Collection $providers */
        $providers = $providerRepository->getFiltered(['search' => $searchString], 0);

        /** @var Collection $services */
        $services = $serviceRepository->getByCriteria(['search' => $searchString]);

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $result['providers'][] = $provider->getId()->getValue();
        }

        /** @var Service $service */
        foreach ($services->getItems() as $service) {
            $result['services'][] = $service->getId()->getValue();
        }

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Service         $service
     * @param Appointment     $appointment
     * @param Payment         $payment
     * @param CustomerBooking $booking
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function isAppointmentStatusChangedWithBooking($service, $appointment, $payment, $booking)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $defaultBookingStatus = $settingsService
            ->getEntitySettings($service->getSettings())
            ->getGeneralSettings()
            ->getDefaultAppointmentStatus();

        if ($payment && $payment->getAmount()->getValue() > 0) {
            /** @var ReservationServiceInterface $reservationService */
            $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

            $paymentRepository->updateFieldById(
                $payment->getId()->getValue(),
                $reservationService->getPaymentAmount($booking, $service)['price'] > $payment->getAmount()->getValue() ?
                    PaymentStatus::PARTIALLY_PAID : PaymentStatus::PAID,
                'status'
            );
        }

        if (
            $defaultBookingStatus === BookingStatus::APPROVED &&
            $booking->getStatus()->getValue() === BookingStatus::PENDING
        ) {
            $oldBookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

            $oldAppointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment(
                $service,
                $oldBookingsCount
            );

            $booking->setChangedStatus(new BooleanValueObject(true));
            $booking->setStatus(new BookingStatus(BookingStatus::APPROVED));


            $newBookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

            $newAppointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment(
                $service,
                $newBookingsCount
            );

            $appointmentRepository->updateFieldById(
                $appointment->getId()->getValue(),
                $newAppointmentStatus,
                'status'
            );

            $bookingRepository->updateFieldById(
                $booking->getId()->getValue(),
                $newAppointmentStatus,
                'status'
            );

            $appointment->setStatus(new BookingStatus($newAppointmentStatus));

            return $oldAppointmentStatus === BookingStatus::PENDING &&
                $newAppointmentStatus === BookingStatus::APPROVED;
        }

        return false;
    }

    /**
     * @param Appointment $appointment
     * @param CustomerBooking $removedBooking
     *
     * @return array
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     */
    public function removeBookingFromGroupAppointment($appointment, $removedBooking)
    {
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $this->container->get('application.booking.booking.service');

        /** @var AppointmentApplicationService $appointmentApplicationService */
        $appointmentApplicationService = $this->container->get('application.booking.appointment.service');

        /** @var BookableApplicationService $bookableApplicationService */
        $bookableApplicationService = $this->container->get('application.bookable.service');

        /** @var AppointmentDomainService $appointmentDomainService */
        $appointmentDomainService = $this->container->get('domain.booking.appointment.service');

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Appointment $originalAppointment */
        $originalAppointment = AppointmentFactory::create($appointment->toArray());

        /** @var Service $service */
        $service = $bookableApplicationService->getAppointmentService(
            $appointment->getServiceId()->getValue(),
            $appointment->getProviderId()->getValue()
        );

        $appointment->getBookings()->deleteItem($removedBooking->getId()->getValue());

        $appointmentStatus = $appointmentDomainService->getAppointmentStatusWhenEditAppointment(
            $service,
            $appointmentDomainService->getBookingsStatusesCount($appointment)
        );

        $appointment->setStatus(new BookingStatus($appointmentStatus));

        $appointmentStatusChanged = $appointmentApplicationService->isAppointmentStatusChanged(
            $appointment,
            $originalAppointment
        );

        if ($appointmentStatusChanged) {
            $appointmentRepository->updateFieldById(
                $appointment->getId()->getValue(),
                $appointment->getStatus()->getValue(),
                'status'
            );

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if (
                    (
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED &&
                    $appointment->getStatus()->getValue() === BookingStatus::PENDING
                    )
                ) {
                    $booking->setChangedStatus(new BooleanValueObject(true));
                }
            }
        }

        $appointment->setRescheduled(new BooleanValueObject(false));

        $appointmentArray = $appointment->toArray();

        $bookingsWithChangedStatus = $bookingApplicationService->getBookingsWithChangedStatus(
            $appointmentArray,
            $originalAppointment->toArray()
        );

        /** @var Collection $removedBookings */
        $removedBookings = new Collection();

        $removedBookings->addItem(
            CustomerBookingFactory::create($removedBooking->toArray()),
            $removedBooking->getId()->getValue()
        );

        $customFieldService->deleteUploadedFilesForDeletedBookings(
            $appointment->getBookings(),
            $removedBookings
        );

        return [
            Entities::APPOINTMENT          => $appointmentArray,
            'bookingsWithChangedStatus'    => $bookingsWithChangedStatus,
            'bookingDeleted'               => true,
            'appointmentDeleted'           => false,
            'appointmentStatusChanged'     => $appointmentStatusChanged,
            'appointmentRescheduled'       => false,
            'appointmentEmployeeChanged'   => null,
            'appointmentZoomUserChanged'   => false,
            'appointmentZoomUsersLicenced' => false,
        ];
    }

    /**
     * @param Appointment     $appointment
     * @param CustomerBooking $removedBooking
     *
     * @return array
     *
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function removeBookingFromNonGroupAppointment($appointment, $removedBooking)
    {
        /** @var BookingApplicationService $bookingApplicationService */
        $bookingApplicationService = $this->container->get('application.booking.booking.service');

        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        /** @var Collection $removedBookings */
        $removedBookings = new Collection();

        $removedBookings->addItem(
            CustomerBookingFactory::create($removedBooking->toArray()),
            $removedBooking->getId()->getValue()
        );

        $appointment->setStatus(new BookingStatus(BookingStatus::REJECTED));

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if ($bookingApplicationService->isBookingApprovedOrPending($booking->getStatus()->getValue())) {
                $booking->setChangedStatus(new BooleanValueObject(true));
            }
        }

        $customFieldService->deleteUploadedFilesForDeletedBookings(
            new Collection(),
            $appointment->getBookings()
        );

        return [
            Entities::APPOINTMENT       => $appointment->toArray(),
            'bookingsWithChangedStatus' => $removedBookings->toArray(),
            'bookingDeleted'            => true,
            'appointmentDeleted'        => true,
        ];
    }

    /**
     * @param CustomerBooking $booking
     * @param Collection      $ignoredBookings
     * @param int             $serviceId
     * @param array           $paymentData
     *
     * @return boolean
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function processPackageAppointmentBooking($booking, $ignoredBookings, $serviceId, &$paymentData)
    {
        /** @var PackageCustomerServiceRepository $packageCustomerServiceRepository */
        $packageCustomerServiceRepository = $this->container->get('domain.bookable.packageCustomerService.repository');

        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var AbstractPackageApplicationService $packageApplicationService */
        $packageApplicationService = $this->container->get('application.bookable.package');

        if (
            (!$booking->getId() || !$ignoredBookings->keyExists($booking->getId()->getValue())) &&
            $booking->getPackageCustomerService() &&
            $booking->getPackageCustomerService()->getPackageCustomer() &&
            $booking->getPackageCustomerService()->getPackageCustomer()->getId()
        ) {
            /** @var Collection $packageCustomerServices */
            $packageCustomerServices = $packageCustomerServiceRepository->getByEntityId(
                $booking->getPackageCustomerService()->getPackageCustomer()->getId()->getValue(),
                'packageCustomerId'
            );

            $newPackageCustomerService = null;

            /** @var PackageCustomerService $packageCustomerService */
            foreach ($packageCustomerServices->getItems() as $packageCustomerService) {
                if ($packageCustomerService->getServiceId()->getValue() === $serviceId) {
                    $newPackageCustomerService = $packageCustomerService;

                    break;
                }
            }

            if (
                !$newPackageCustomerService ||
                !$packageApplicationService->isBookingAvailableForPurchasedPackage(
                    $newPackageCustomerService->getId()->getValue(),
                    $booking->getCustomerId()->getValue(),
                    false
                )
            ) {
                return false;
            }

            $booking->getPackageCustomerService()->setId(new Id($newPackageCustomerService->getId()->getValue()));

            if ($booking->getId() && $booking->getId()->getValue()) {
                $customerBookingRepository->updateFieldById(
                    $booking->getId()->getValue(),
                    $newPackageCustomerService->getId()->getValue(),
                    'packageCustomerServiceId'
                );
            }

            $paymentData = null;
        }

        return true;
    }

    /**
     * @param Appointment $newAppointment
     * @param Appointment $oldAppointment
     *
     * @return bool
     *
     * @throws ContainerValueNotFoundException
     */
    public function appointmentDetailsChanged($newAppointment, $oldAppointment)
    {
        if (
            ($oldAppointment->getLocationId() ? $oldAppointment->getLocationId()->getValue() : null) !==
            ($newAppointment->getLocationId() ? $newAppointment->getLocationId()->getValue() : null)
        ) {
            return true;
        }
        if ($oldAppointment->getLessonSpace() !== $newAppointment->getLessonSpace()) {
            return true;
        }
        return $oldAppointment->getProviderId()->getValue() !== $newAppointment->getProviderId()->getValue();
    }

    /**
     * @param CustomerBooking $newBooking
     * @param CustomerBooking     $oldBooking
     *
     * @return bool
     *
     * @throws ContainerValueNotFoundException
     */
    public function bookingDetailsChanged($newBooking, $oldBooking)
    {
        if ($oldBooking->getPersons()->getValue() !== $newBooking->getPersons()->getValue()) {
            return true;
        }
        if (
            ($oldBooking->getDuration() ? $oldBooking->getDuration()->getValue() : null) !==
            ($newBooking->getDuration() ? $newBooking->getDuration()->getValue() : null)
        ) {
            return true;
        }
        if ($newBooking->getExtras()->length() !== $oldBooking->getExtras()->length()) {
            return true;
        } else {
            foreach ($newBooking->getExtras()->toArray() as $newExtra) {
                $extraIndex = array_search($newExtra['id'], array_column($oldBooking->getExtras()->toArray(), 'id'));
                if ($extraIndex === false || $newExtra['quantity'] !== $oldBooking->getExtras()->toArray()[$extraIndex]['quantity']) {
                    return true;
                }
            }
        }

        $newCustomFields = $newBooking->getCustomFields() && $newBooking->getCustomFields()->getValue() ?
            json_decode($newBooking->getCustomFields()->getValue(), true) : null;
        $oldCustomFields = $oldBooking->getCustomFields() && $oldBooking->getCustomFields()->getValue() ?
            json_decode($oldBooking->getCustomFields()->getValue(), true) : null;

        if ($newCustomFields) {
            $newCustomFields = array_filter(
                $newCustomFields,
                function ($k) {
                    return !empty($k['value']);
                }
            );
        }
        if ($oldCustomFields) {
            $oldCustomFields = array_filter(
                $oldCustomFields,
                function ($k) {
                    return !empty($k['value']);
                }
            );
        }

        if (($newCustomFields ? count($newCustomFields) : null) !== ($oldCustomFields ? count($oldCustomFields) : null)) {
            return true;
        } else {
            foreach ((array)$newCustomFields as $index => $newCf) {
                $cfIndex = is_array($oldCustomFields) && !empty($oldCustomFields[$index]) ? $index : false;
                if ($cfIndex === false || $newCf['value'] !== $oldCustomFields[$cfIndex]['value']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Appointment $appointment
     * @param Service     $service
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function calculateAndSetAppointmentEnd($appointment, $service)
    {
        $appointment->setBookingEnd(
            new DateTimeValue(
                DateTimeService::getCustomDateTimeObject(
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                )->modify('+' . $this->getAppointmentLengthTime($appointment, $service) . ' second')
            )
        );
    }

    /**
     * @param Service $service
     *
     * @return bool
     *
     * @throws ContainerValueNotFoundException
     */
    public function isDurationPricingType($service)
    {
        if ($service->getCustomPricing()) {
            $customPricing = json_decode($service->getCustomPricing()->getValue(), true);

            if (
                $customPricing !== null &&
                ($customPricing['enabled'] === true || $customPricing['enabled'] === 'duration')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Service $service
     *
     * @return bool
     *
     * @throws ContainerValueNotFoundException
     */
    public function isPersonPricingType($service)
    {
        if ($service->getCustomPricing()) {
            $customPricing = json_decode($service->getCustomPricing()->getValue(), true);

            if (
                $customPricing !== null &&
                $customPricing['enabled'] === 'person' &&
                $customPricing['persons']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Service $service
     *
     * @return boolean
     */
    public function isPeriodCustomPricing($service)
    {
        if ($service->getCustomPricing()) {
            $customPricing = json_decode($service->getCustomPricing()->getValue(), true);

            if (
                Licence\Licence::$licence !== 'Lite' &&
                Licence\Licence::$licence !== 'Starter' &&
                Licence\Licence::$licence !== 'Basic' &&
                $customPricing !== null &&
                $customPricing['enabled'] === 'period' &&
                $customPricing['periods']
            ) {
                return true;
            }
        }

        return false;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Service              $service
     * @param CustomerBooking|null $booking
     * @param Provider             $provider
     * @param string               $bookingStart
     *
     * @return float
     */
    public function getBookingPriceForService($service, $booking, $provider, $bookingStart)
    {
        if ($service->getCustomPricing()) {
            $customPricing = json_decode($service->getCustomPricing()->getValue(), true);

            if (
                $customPricing !== null &&
                $booking &&
                $booking->getDuration() &&
                $booking->getDuration()->getValue() &&
                ($customPricing['enabled'] === true || $customPricing['enabled'] === 'duration') &&
                array_key_exists($booking->getDuration()->getValue(), $customPricing['durations'])
            ) {
                return $customPricing['durations'][$booking->getDuration()->getValue()]['price'];
            } elseif (
                $customPricing !== null &&
                $customPricing['enabled'] === 'person' &&
                $customPricing['persons'] &&
                $booking &&
                $booking->getPersons() &&
                $booking->getPersons()->getValue()
            ) {
                ksort($customPricing['persons'], SORT_NUMERIC);

                $filteredRanges = array_filter(
                    array_keys($customPricing['persons']),
                    function ($i) use ($booking) {
                        return $booking->getPersons()->getValue() >= $i;
                    }
                );

                $item = !$filteredRanges
                    ? ['price' => $service->getPrice()->getValue()]
                    : $customPricing['persons'][array_pop($filteredRanges)];

                return $item['price'];
            } elseif (
                Licence\Licence::$licence !== 'Lite' &&
                Licence\Licence::$licence !== 'Starter' &&
                Licence\Licence::$licence !== 'Basic' &&
                $customPricing !== null &&
                $customPricing['enabled'] === 'period' &&
                $customPricing['periods']
            ) {
                /** @var ProviderService $providerService */
                $providerService = $this->container->get('domain.user.provider.service');

                /** @var IntervalService $intervalService */
                $intervalService = $this->container->get('domain.interval.service');

                $start = DateTimeService::getCustomDateTimeObject(
                    $bookingStart
                )->setTimezone(
                    DateTimeService::createTimeZone(
                        $provider->getTimeZone()
                            ? $provider->getTimeZone()->getValue()
                            : DateTimeService::getTimeZone()->getName()
                    )
                );

                $price = $providerService->getDateTimePrice(
                    $providerService->getCustomPricing(
                        $service,
                        $provider->getTimeZone()
                            ? $provider->getTimeZone()->getValue()
                            : DateTimeService::getTimeZone()->getName()
                    ),
                    $start->format('Y-m-d'),
                    $intervalService->getSeconds($start->format('H:i:s')),
                    $provider->getTimeZone()
                        ? $provider->getTimeZone()->getValue()
                        : DateTimeService::getTimeZone()->getName()
                );

                return $price !== null ? $price : $service->getPrice()->getValue();
            }
        }

        return $service->getPrice()->getValue();
    }

    /**
     * @param Appointment $appointment
     * @param Service     $service
     *
     * @return int
     *
     * @throws ContainerValueNotFoundException
     */
    public function getMaximumBookingDuration($appointment, $service)
    {
        $maximumDuration = 0;

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if (
                (
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED ||
                    $booking->getStatus()->getValue() === BookingStatus::PENDING
                ) &&
                $booking->getDuration() &&
                $booking->getDuration()->getValue() &&
                $booking->getDuration()->getValue() > $maximumDuration
            ) {
                $maximumDuration = $booking->getDuration()->getValue();
            }
        }

        return $maximumDuration ? $maximumDuration : $service->getDuration()->getValue();
    }

    /**
     * @param int $bookingId
     *
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function approveBooking($bookingId)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');

        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        /** @var Appointment $appointment **/
        $appointment = $appointmentRepository->getByBookingId($bookingId);

        $bookingRepository->updateFieldById($bookingId, BookingStatus::APPROVED, 'status');

        /** @var CustomerBooking $booking **/
        $booking = $appointment->getBookings()->getItem($bookingId);

        $booking->setStatus(new BookingStatus(BookingStatus::APPROVED));

        $booking->setChangedStatus(new BooleanValueObject(true));

        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment(
            $bookableAS->getAppointmentService(
                $appointment->getServiceId()->getValue(),
                $appointment->getProviderId()->getValue()
            ),
            $appointmentDS->getBookingsStatusesCount($appointment)
        );

        $appointmentRepository->updateFieldById($appointment->getId()->getValue(), $appointmentStatus, 'status');

        if (
            $appointment->getStatus()->getValue() !== $appointmentStatus &&
            $appointmentStatus === BookingStatus::APPROVED
        ) {
            $appointment->setStatus(new BookingStatus(BookingStatus::APPROVED));

            $bookingsWithChangedStatus = [];

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($booking->getStatus()->getValue() === BookingStatus::APPROVED) {
                    $booking->setChangedStatus(new BooleanValueObject(true));

                    $bookingsWithChangedStatus[] = $booking->toArray();
                }
            }

            $result = new CommandResult();

            $result->setData(
                [
                    Entities::APPOINTMENT       => $appointment->toArray(),
                    'bookingsWithChangedStatus' => $bookingsWithChangedStatus,
                    'oldStatus'                 => null,
                    'createPaymentLink'         => false,
                ]
            );

            AppointmentStatusUpdatedEventHandler::handle($result, $this->container);
        }
    }

    /**
     * @param int $providerId
     * @param int $oldProviderId
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getUserConnectionChanges($providerId, $oldProviderId)
    {
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var AbstractZoomApplicationService $zoomService */
        $zoomService = $this->container->get('application.zoom.service');

        $appointmentEmployeeChanged = null;

        $appointmentZoomUserChanged = false;

        $appointmentZoomUsersLicenced = false;

        if ($providerId !== $oldProviderId) {
            $appointmentEmployeeChanged = $oldProviderId;

            $provider = $providerRepository->getById($providerId);

            $oldProvider = $providerRepository->getById($oldProviderId);

            if (
                $provider && $oldProvider && $provider->getZoomUserId() && $oldProvider->getZoomUserId() &&
                $provider->getZoomUserId()->getValue() !== $oldProvider->getZoomUserId()->getValue()
            ) {
                $appointmentZoomUserChanged = true;

                $zoomUserType = 0;

                $zoomOldUserType = 0;

                $zoomResult = $zoomService->getUsers();

                if (
                    !(isset($zoomResult['code']) && $zoomResult['code'] === 124) &&
                    !($zoomResult['users'] === null && isset($zoomResult['message']))
                ) {
                    $zoomUsers = $zoomResult['users'];
                    foreach ($zoomUsers as $key => $val) {
                        if ($val['id'] === $provider->getZoomUserId()->getValue()) {
                            $zoomUserType = $val['type'];
                        }
                        if ($val['id'] === $oldProvider->getZoomUserId()->getValue()) {
                            $zoomOldUserType = $val['type'];
                        }
                    }
                }
                if ($zoomOldUserType > 1 && $zoomUserType > 1) {
                    $appointmentZoomUsersLicenced = true;
                }
            }
        }

        return [
            'appointmentEmployeeChanged'   => $appointmentEmployeeChanged,
            'appointmentZoomUserChanged'   => $appointmentZoomUserChanged,
            'appointmentZoomUsersLicenced' => $appointmentZoomUsersLicenced,
        ];
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Appointment          $appointment
     * @param Service              $service
     * @param CustomerBooking|null $booking
     * @param string|null          $newBookingStatus
     * @param string|null          $abandonedAppointmentStatus
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function manageAppointmentStatusByBooking(
        $appointment,
        $service,
        $booking,
        $newBookingStatus,
        $abandonedAppointmentStatus
    ) {
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');

        $oldAppointmentStatus = $appointment->getStatus()->getValue();

        $oldBookingStatus = null;

        if ($booking) {
            /** @var CustomerBooking $appointmentBooking */
            $appointmentBooking = $this->getAppointmentBooking($appointment, $booking->getId()->getValue());

            $oldBookingStatus = $booking->getStatus()->getValue();

            $appointmentBooking->setStatus(new BookingStatus($newBookingStatus));

            $booking->setStatus(new BookingStatus($newBookingStatus));
        }

        $newAppointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment(
            $service,
            $appointmentDS->getBookingsStatusesCount($appointment)
        );

        if ($booking) {
            $booking->setChangedStatus(
                new BooleanValueObject(
                    (
                        $bookingAS->isBookingCanceledOrRejectedOrNoShow($oldBookingStatus) &&
                        $bookingAS->isBookingApprovedOrPending($newBookingStatus)
                    ) || (
                        $bookingAS->isBookingCanceledOrRejectedOrNoShow($newBookingStatus) &&
                        $bookingAS->isBookingApprovedOrPending($oldBookingStatus)
                    ) || $bookingAS->isAppointmentStatusChangedForBooking(
                        $newBookingStatus,
                        $oldBookingStatus,
                        $newAppointmentStatus,
                        $abandonedAppointmentStatus !== null ? $abandonedAppointmentStatus : $oldAppointmentStatus
                    )
                )
            );
        }

        $appointment->setStatus(
            new BookingStatus(
                $newAppointmentStatus
            )
        );

        $appointmentStatusChanged = $newAppointmentStatus !== $oldAppointmentStatus;

        /** @var CustomerBooking $customerBooking */
        foreach ($appointment->getBookings()->getItems() as $customerBooking) {
            if ($booking !== null && $booking->getId()->getValue() === $customerBooking->getId()->getValue()) {
                $customerBooking->setStatus(new BookingStatus($booking->getStatus()->getValue()));

                $customerBooking->setChangedStatus(new BooleanValueObject($booking->isChangedStatus()->getValue()));
            } else {
                $customerBooking->setChangedStatus(
                    new BooleanValueObject(
                        $appointmentStatusChanged &&
                        $bookingAS->isAppointmentStatusChangedForBooking(
                            $customerBooking->getStatus()->getValue(),
                            $customerBooking->getStatus()->getValue(),
                            $newAppointmentStatus,
                            $oldAppointmentStatus
                        )
                    )
                );
            }
        }

        $appointment->setChangedStatus(new BooleanValueObject($appointmentStatusChanged));

        return $appointmentStatusChanged;
    }
}
