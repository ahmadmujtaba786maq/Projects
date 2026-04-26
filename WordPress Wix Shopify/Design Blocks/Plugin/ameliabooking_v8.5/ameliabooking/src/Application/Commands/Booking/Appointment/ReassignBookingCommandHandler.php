<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\Reservation\AppointmentReservationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService as ApplicationTimeSlotService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;

/**
 * Class ReassignBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class ReassignBookingCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
    ];

    /**
     * @param ReassignBookingCommand $command
     *
     * @return CommandResult
     *
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(ReassignBookingCommand $command)
    {
        $this->checkMandatoryFields($command);

        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->container->get('domain.users.customers.repository');
        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');
        /** @var AppointmentReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        try {
            /** @var AbstractUser $user */
            $user = $command->getUserApplicationService()->authorization(
                $command->getPage() === 'cabinet' ? $command->getToken() : null,
                $command->getCabinetType()
            );
        } catch (AuthorizationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData(
                [
                    'reauthorize' => true
                ]
            );

            return $result;
        }

        if (
            $userAS->isCustomer($user) && !$settingsDS->getSetting('roles', 'allowCustomerReschedule')
        ) {
            throw new AccessDeniedException('You are not allowed to update booking');
        }

        /** @var Appointment $oldAppointment */
        $oldAppointment = $reservationService->getReservationByBookingId((int)$command->getArg('id'));

        $oldAppointment->setInitialBookingStart(
            new DateTimeValue(clone $oldAppointment->getBookingStart()->getValue())
        );

        $oldAppointment->setInitialBookingEnd(
            new DateTimeValue(clone $oldAppointment->getBookingEnd()->getValue())
        );

        /** @var CustomerBooking $booking */
        $booking = $oldAppointment->getBookings()->getItem((int)$command->getArg('id'));

        if ($command->getField('customFields')) {
            $customFields = json_encode($command->getField('customFields'));

            if (!$booking->getCustomFields() || $booking->getCustomFields()->getValue() !== $customFields) {
                $booking->setUpdated(new BooleanValueObject(true));
            }

            $booking->setCustomFields(new Json($customFields));
        }

        /** @var CustomerBooking $oldAppointmentBooking */
        foreach ($oldAppointment->getBookings()->getItems() as $oldAppointmentBooking) {
            if (
                $userAS->isAmeliaUser($user) &&
                $userAS->isCustomer($user) &&
                ($booking->getId()->getValue() === $oldAppointmentBooking->getId()->getValue()) &&
                ($user->getId() && $oldAppointmentBooking->getCustomerId()->getValue() !== $user->getId()->getValue())
            ) {
                throw new AccessDeniedException('You are not allowed to update booking');
            }
        }

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService(
            $oldAppointment->getServiceId()->getValue(),
            $oldAppointment->getProviderId()->getValue()
        );

        $requiredServiceId = $command->getField('serviceId') ?: $oldAppointment->getServiceId()->getValue();

        $requiredProviderId = $command->getField('providerId') ?: $oldAppointment->getProviderId()->getValue();

        $requiredLocationId = $command->getField('locationId')
            ?: (
                    $oldAppointment->getLocationId() ? $oldAppointment->getLocationId()->getValue() : null
            );

        $requiredBookingStatus = $command->getField('status') ?: $booking->getStatus()->getValue();

        if (
            $userAS->isCustomer($user) &&
            (
                $requiredBookingStatus !== $booking->getStatus()->getValue() ||
                $requiredServiceId !== $oldAppointment->getServiceId()->getValue() ||
                $requiredProviderId !== $oldAppointment->getProviderId()->getValue() ||
                $requiredLocationId !== (
                $oldAppointment->getLocationId() ? $oldAppointment->getLocationId()->getValue() : null
                )
            )
        ) {
            throw new AccessDeniedException('You are not allowed to update booking');
        }

        $excludedAppointmentId = $oldAppointment->getBookings()->length() > 1 &&
        (
            $oldAppointment->getServiceId()->getValue() !== $requiredServiceId ||
            $oldAppointment->getProviderId()->getValue() !== $requiredProviderId ||
            (
                $oldAppointment->getLocationId() &&
                $oldAppointment->getLocationId()->getValue() !== $requiredLocationId
            )
        )
            ? null
            : $oldAppointment->getId()->getValue();

        /** @var Service $requiredService */
        $requiredService =
            ($requiredServiceId !== $oldAppointment->getServiceId()->getValue()) ||
            ($requiredProviderId !== $oldAppointment->getProviderId()->getValue())
                ? $bookableAS->getAppointmentService($requiredServiceId, $requiredProviderId)
                : $service;

        $minimumRescheduleTimeInSeconds = $settingsDS
            ->getEntitySettings($service->getSettings())
            ->getGeneralSettings()
            ->getMinimumTimeRequirementPriorToRescheduling();

        if ($user && $user->getType() === AbstractUser::USER_ROLE_CUSTOMER) {
            try {
                $reservationService->inspectMinimumCancellationTime(
                    $oldAppointment->getBookingStart()->getValue(),
                    $minimumRescheduleTimeInSeconds
                );
            } catch (BookingCancellationException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('You are not allowed to update booking');
                $result->setData(
                    [
                        'rescheduleBookingUnavailable' => true
                    ]
                );

                return $result;
            }
        }

        $bookingStart = $command->getField('bookingStart')
            ? substr($command->getField('bookingStart'), 0, 16) . ':00'
            : $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        $bookingStartInUtc = DateTimeService::getCustomDateTimeObject(
            $bookingStart
        )->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i');

        if ($command->getField('timeZone') === 'UTC') {
            $bookingStart = DateTimeService::getCustomDateTimeFromUtc(
                $bookingStart
            );
        } elseif ($command->getField('timeZone')) {
            $bookingStart = DateTimeService::getDateTimeObjectInTimeZone(
                $bookingStart,
                $command->getField('timeZone')
            )->setTimezone(DateTimeService::getTimeZone())->format('Y-m-d H:i:s');
        } elseif (
            $command->getField('utcOffset') !== null &&
            $settingsDS->getSetting('general', 'showClientTimeZone')
        ) {
            $bookingStart = DateTimeService::getCustomDateTimeFromUtc(
                $bookingStart
            );
        }

        $bookingRescheduled = $bookingStart !== $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        if (
            !$bookingRescheduled && (
                (
                    $requiredBookingStatus &&
                    $booking->getStatus()->getValue() !== $requiredBookingStatus
                ) ||
                $oldAppointment->getProviderId()->getValue() !== $requiredProviderId
            )
        ) {
            $booking->setUpdated(new BooleanValueObject(true));

            $oldAppointment->getBookings()->getItem($booking->getId()->getValue())->setUpdated(
                new BooleanValueObject(true)
            );
        }

        /** @var ApplicationTimeSlotService $applicationTimeSlotService */
        $applicationTimeSlotService = $this->container->get('application.timeSlot.service');

        if (
            !$applicationTimeSlotService->isSlotFree(
                $requiredService,
                DateTimeService::getCustomDateTimeObject(
                    $bookingStart
                ),
                DateTimeService::getCustomDateTimeObject(
                    $bookingStart
                ),
                DateTimeService::getCustomDateTimeObject(
                    $bookingStart
                ),
                $requiredProviderId,
                $requiredLocationId,
                $booking->getExtras()->getItems(),
                $excludedAppointmentId,
                $booking->getPersons()->getValue(),
                $user->getType() === AbstractUser::USER_ROLE_CUSTOMER
            )
        ) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
            $result->setData(
                [
                    'timeSlotUnavailable' => true
                ]
            );

            return $result;
        }

        /** @var AppointmentReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

        if (
            $reservationService->checkLimitsPerCustomer(
                $requiredService,
                $booking->getCustomerId()->getValue(),
                DateTimeService::getCustomDateTimeObject($bookingStart),
                $booking->getId()->getValue()
            )
        ) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
            $result->setData(
                [
                    'timeSlotUnavailable' => true
                ]
            );

            return $result;
        }

        $setTimeZone = false;

        if ($booking->getInfo() && $booking->getInfo()->getValue()) {
            $info = json_decode($booking->getInfo()->getValue(), true);

            if (empty($info['timeZone'])) {
                $setTimeZone = true;
            }
        } elseif (!$booking->getInfo() || $booking->getInfo()->getValue() === null) {
            $setTimeZone = true;
        }

        if (
            $setTimeZone &&
            (!$booking->getUtcOffset() || $booking->getInfo()->getValue() === null) &&
            $userAS->isCustomer($user) &&
            $command->getField('timeZone') &&
            $command->getField('timeZone') !== 'UTC' &&
            $command->getField('utcOffset') !== null &&
            $settingsDS->getSetting('general', 'showClientTimeZone')
        ) {
            /** @var Customer $customer */
            $customer = $customerRepository->getById($booking->getCustomerId()->getValue());

            $booking->setInfo(
                new Json(
                    json_encode(
                        [
                            'firstName' => $customer->getFirstName()->getValue(),
                            'lastName'  => $customer->getLastName()->getValue(),
                            'phone'     => null,
                            'locale'    => null,
                            'timeZone'  => $command->getField('timeZone'),
                            'urlParams' => null,
                        ]
                    )
                )
            );

            $bookingRepository->updateFieldById(
                $booking->getId()->getValue(),
                $booking->getInfo()->getValue(),
                'info'
            );

            $booking->setUtcOffset(new IntegerValue($command->getField('utcOffset')));

            $bookingRepository->updateFieldById(
                $booking->getId()->getValue(),
                $booking->getUtcOffset()->getValue(),
                'utcOffset'
            );
        }

        /** @var Collection $existingAppointments */
        $existingAppointments = $appointmentRepository->getFiltered(
            [
                'dates'     => [$bookingStart, $bookingStart],
                'services'  => [$requiredServiceId],
                'providers' => [$requiredProviderId],
            ]
        );

        $userConnectionChanges = $appointmentAS->getUserConnectionChanges(
            $requiredProviderId,
            $oldAppointment->getProviderId()->getValue()
        );

        /** @var Appointment|null $newAppointment */
        $newAppointment = null;

        /** @var Appointment $existingAppointment */
        $existingAppointment = $existingAppointments->length()
            ? $existingAppointments->getItem($existingAppointments->keys()[0])
            : null;

        if (
            $existingAppointment &&
            $existingAppointment->getId()->getValue() === $oldAppointment->getId()->getValue()
        ) {
            $existingAppointment = null;
        }

        if ($existingAppointment) {
            /** @var CustomerBooking $customerBooking */
            foreach ($existingAppointment->getBookings()->getItems() as $customerBooking) {
                if (
                    $customerBooking->getCustomerId()->getValue() === $booking->getCustomerId()->getValue() &&
                    $bookingAS->isBookingApprovedOrPending($booking->getStatus()->getValue())
                ) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setData(
                        [
                            'customerAlreadyBooked' => true
                        ]
                    );

                    return $result;
                }
            }
        }

        $bookingStatus = $userAS->isCustomer($user)
            ? $settingsDS
                ->getEntitySettings($requiredService->getSettings())
                ->getGeneralSettings()
                ->getDefaultAppointmentStatus()
            : $requiredBookingStatus;

        $existingAppointmentStatusChanged = false;

        $oldAppointmentStatus = $oldAppointment->getStatus()->getValue();

        $appointmentRepository->beginTransaction();

        do_action('amelia_before_booking_rescheduled', $oldAppointment->toArray(), $booking->toArray(), $bookingStart);

        if (
            $existingAppointment === null &&
            (
                $oldAppointment->getBookings()->length() === 1 ||
                (
                    !$bookingRescheduled &&
                    $requiredServiceId === $oldAppointment->getServiceId()->getValue() &&
                    $requiredProviderId === $oldAppointment->getProviderId()->getValue() &&
                    (
                        !$oldAppointment->getLocationId() ||
                        $oldAppointment->getLocationId()->getValue() === $requiredLocationId
                    )
                )
            )
        ) {
            $oldAppointment->setProviderId(new Id($requiredProviderId));
            $oldAppointment->setServiceId(new Id($requiredServiceId));

            if ($requiredLocationId) {
                $oldAppointment->setLocationId(new Id($requiredLocationId));
            }

            if ($bookingStart !== $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i')) {
                $bookingAS->bookingRescheduled(
                    $oldAppointment->getId()->getValue(),
                    Entities::APPOINTMENT,
                    $booking->getCustomerId()->getValue(),
                    Entities::CUSTOMER
                );

                $bookingAS->bookingRescheduled(
                    $oldAppointment->getId()->getValue(),
                    Entities::APPOINTMENT,
                    $oldAppointment->getProviderId()->getValue(),
                    Entities::PROVIDER
                );
            }

            $oldAppointment->setBookingStart(
                new DateTimeValue(
                    DateTimeService::getCustomDateTimeObject(
                        $bookingStart
                    )
                )
            );

            $oldAppointment->setBookingEnd(
                new DateTimeValue(
                    DateTimeService::getCustomDateTimeObject($bookingStart)
                        ->modify(
                            '+' . $appointmentAS->getAppointmentLengthTime($oldAppointment, $service) . ' second'
                        )
                )
            );

            $oldAppointmentStatusChanged = $appointmentAS->manageAppointmentStatusByBooking(
                $oldAppointment,
                $requiredService,
                $booking,
                $bookingStatus,
                $oldAppointment->getStatus()->getValue()
            );

            if ($command->getField('internalNotes')) {
                $oldAppointment->setInternalNotes(new Description($command->getField('internalNotes')));
            }

            $paymentAS->updateBookingPaymentDate($booking, $bookingStartInUtc);

            $appointmentRepository->update($oldAppointment->getId()->getValue(), $oldAppointment);

            $oldAppointment->setRescheduled(new BooleanValueObject($bookingRescheduled));

            $reservationService->updateWooCommerceOrder($booking, $oldAppointment);
        } else {
            $oldAppointment->getBookings()->deleteItem($booking->getId()->getValue());

            if ($existingAppointment !== null) {
                $booking->setAppointmentId($existingAppointment->getId());

                $existingAppointment->getBookings()->addItem($booking, $booking->getId()->getValue());

                $existingAppointmentStatusChanged = $appointmentAS->manageAppointmentStatusByBooking(
                    $existingAppointment,
                    $requiredService,
                    $booking,
                    $bookingStatus,
                    $oldAppointment->getStatus()->getValue()
                );

                $existingAppointment->setBookingEnd(
                    new DateTimeValue(
                        DateTimeService::getCustomDateTimeObject($bookingStart)
                            ->modify(
                                '+' . $appointmentAS->getAppointmentLengthTime($existingAppointment, $requiredService) . ' second'
                            )
                    )
                );

                $bookingRepository->updateFieldById(
                    $booking->getId()->getValue(),
                    $existingAppointment->getId()->getValue(),
                    'appointmentId'
                );

                $paymentAS->updateBookingPaymentDate($booking, $bookingStartInUtc);

                $appointmentRepository->update($existingAppointment->getId()->getValue(), $existingAppointment);

                $reservationService->updateWooCommerceOrder($booking, $existingAppointment);
            } elseif (
                $bookingRescheduled ||
                $oldAppointment->getProviderId()->getValue() !== $requiredProviderId ||
                $oldAppointment->getServiceId()->getValue() !== $requiredServiceId ||
                (
                    $requiredLocationId &&
                    $oldAppointment->getLocationId() &&
                    $oldAppointment->getLocationId()->getValue() !== $requiredLocationId
                )
            ) {
                $oldAppointment->setProviderId(new Id($requiredProviderId));
                $oldAppointment->setServiceId(new Id($requiredServiceId));

                if ($requiredLocationId) {
                    $oldAppointment->setLocationId(new Id($requiredLocationId));
                }

                $newAppointment = AppointmentFactory::create(
                    array_merge(
                        $oldAppointment->toArray(),
                        [
                            'id'                     => null,
                            'googleCalendarEventId'  => null,
                            'outlookCalendarEventId' => null,
                            'zoomMeeting'            => null,
                            'bookings'               => [],
                        ]
                    )
                );

                $newAppointment->getBookings()->addItem($booking, $booking->getId()->getValue());

                $newAppointment->setBookingStart(
                    new DateTimeValue(
                        DateTimeService::getCustomDateTimeObject(
                            $bookingStart
                        )
                    )
                );

                $newAppointment->setBookingEnd(
                    new DateTimeValue(
                        DateTimeService::getCustomDateTimeObject($bookingStart)
                            ->modify(
                                '+' . $appointmentAS->getAppointmentLengthTime($newAppointment, $requiredService) . ' second'
                            )
                    )
                );

                $newAppointment->setRescheduled(new BooleanValueObject($bookingRescheduled));

                $appointmentAS->manageAppointmentStatusByBooking(
                    $newAppointment,
                    $requiredService,
                    $booking,
                    $bookingStatus,
                    $oldAppointment->getStatus()->getValue()
                );

                $newAppointmentId = $appointmentRepository->add($newAppointment);

                $newAppointment->setId(new Id($newAppointmentId));

                $booking->setAppointmentId(new Id($newAppointmentId));

                $bookingRepository->updateFieldById(
                    $booking->getId()->getValue(),
                    $newAppointmentId,
                    'appointmentId'
                );

                $paymentAS->updateBookingPaymentDate($booking, $bookingStartInUtc);

                $reservationService->updateWooCommerceOrder($booking, $newAppointment);
            }

            if ($oldAppointment->getBookings()->length() === 0) {
                $appointmentRepository->delete($oldAppointment->getId()->getValue());

                $oldAppointment->setStatus(new BookingStatus(BookingStatus::CANCELED));

                $oldAppointmentStatusChanged = true;
            } else {
                $oldAppointmentStatusChanged = $appointmentAS->manageAppointmentStatusByBooking(
                    $oldAppointment,
                    $service,
                    null,
                    null,
                    null
                );

                $oldAppointment->setBookingEnd(
                    new DateTimeValue(
                        DateTimeService::getCustomDateTimeObject(
                            $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                        )->modify(
                            '+' . $appointmentAS->getAppointmentLengthTime($oldAppointment, $service) . ' second'
                        )
                    )
                );

                $appointmentRepository->update($oldAppointment->getId()->getValue(), $oldAppointment);
            }
        }

        $bookingRepository->update($booking->getId()->getValue(), $booking);

        if ($bookingRescheduled && $appointmentAS->isPeriodCustomPricing($requiredService)) {
            /** @var Provider $provider */
            $provider = $userRepository->getById($requiredProviderId);

            $price = $appointmentAS->getBookingPriceForService(
                $requiredService,
                null,
                $provider,
                $bookingStart
            );

            $booking->setPrice(new Price($price));

            $bookingRepository->updatePrice($booking->getId()->getValue(), $booking);
        }

        $appointmentRepository->commit();

        do_action('amelia_after_booking_rescheduled', $oldAppointment->toArray(), $booking->toArray(), $bookingStart);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated appointment');
        $result->setData(
            [
                Entities::BOOKING                  => $booking->toArray(),
                'newAppointment'                   => $newAppointment ? $newAppointment->toArray() : null,
                'oldAppointment'                   => $oldAppointment->toArray(),
                'oldAppointmentStatusChanged'      => $oldAppointmentStatusChanged,
                'oldAppointmentStatus'             => $oldAppointmentStatus,
                'bookingRescheduled'               => $bookingRescheduled,
                'bookingEmployeeChanged'           => $userConnectionChanges['appointmentEmployeeChanged'],
                'bookingZoomUserChanged'           => $userConnectionChanges['appointmentZoomUserChanged'],
                'bookingZoomUsersLicenced'         => $userConnectionChanges['appointmentZoomUsersLicenced'],
                'existingAppointment'              => $existingAppointment ? $existingAppointment->toArray() : null,
                'existingAppointmentStatusChanged' => $existingAppointmentStatusChanged,
            ]
        );

        return $result;
    }
}
