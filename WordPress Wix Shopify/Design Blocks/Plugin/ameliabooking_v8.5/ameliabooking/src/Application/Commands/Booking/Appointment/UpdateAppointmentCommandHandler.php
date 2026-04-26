<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\CustomField\AbstractCustomFieldApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\Reservation\AppointmentReservationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Application\Services\Zoom\AbstractZoomApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;

/**
 * Class UpdateAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class UpdateAppointmentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId',
        'id'
    ];

    /**
     * @param UpdateAppointmentCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(UpdateAppointmentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');
        /** @var AbstractCustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');
        /** @var AbstractZoomApplicationService $zoomService */
        $zoomService = $this->container->get('application.zoom.service');
        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');
        /** @var AppointmentReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

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

        if ($userAS->isCustomer($user)) {
            throw new AccessDeniedException('You are not allowed to update appointment');
        }

        if ($userAS->isProvider($user) && !$settingsDS->getSetting('roles', 'allowWriteAppointments')) {
            throw new AccessDeniedException('You are not allowed to update appointment');
        }

        $appointmentData = $command->getFields();

        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntityForAppointment($appointmentData);

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService(
            $command->getFields()['serviceId'],
            $command->getFields()['providerId']
        );

        $appointmentAS->convertTime($appointmentData);

        $reservationService->manageTaxes($appointmentData);

        $removedBookingsData = $command->getField('removedBookings');

        // added check for API call when removedBookings not sent
        $removedBookingsData = empty($removedBookingsData) ? [] : $removedBookingsData;

        $appointment = apply_filters('amelia_before_appointment_updated_filter', $appointmentData, $removedBookingsData, $service ? $service->toArray() : null);

        do_action('amelia_before_appointment_updated', $appointment, $removedBookingsData, $service ? $service->toArray() : null);

        /** @var Appointment $appointment */
        $appointment = $appointmentAS->build($appointmentData, $service);

        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

        /** @var Provider $provider */
        $provider = $providerRepository->getById($appointmentData['providerId']);

        $appointment->setProvider($provider);

        /** @var Appointment $oldAppointment */
        $oldAppointment = $appointmentRepo->getById($appointment->getId()->getValue());

        $appointment->setInitialBookingStart(
            new DateTimeValue(clone $oldAppointment->getBookingStart()->getValue())
        );

        $appointment->setInitialBookingEnd(
            new DateTimeValue(clone $oldAppointment->getBookingEnd()->getValue())
        );

        /** @var CustomerBooking $newBooking */
        foreach ($appointment->getBookings()->getItems() as $newBooking) {
            /** @var CustomerBooking $oldBooking */
            foreach ($oldAppointment->getBookings()->getItems() as $oldBooking) {
                if (
                    $newBooking->getId() &&
                    $newBooking->getId()->getValue() === $oldBooking->getId()->getValue()
                ) {
                    if ($oldBooking->getUtcOffset()) {
                        $newBooking->setUtcOffset($oldBooking->getUtcOffset());
                    }

                    if ($oldBooking->getCreated()) {
                        $newBooking->setCreated($oldBooking->getCreated());
                    }

                    if ($oldBooking->getInfo()) {
                        $newBooking->setInfo($oldBooking->getInfo());
                    }

                    if ($oldBooking->getStatus()->getValue() === $newBooking->getStatus()->getValue()) {
                        $newBooking->setUpdated(
                            new BooleanValueObject(
                                $appointmentAS->appointmentDetailsChanged($appointment, $oldAppointment) ||
                                $appointmentAS->bookingDetailsChanged($newBooking, $oldBooking)
                            )
                        );
                    }
                }
            }
        }

        $appointment->setBookingEnd(
            new DateTimeValue(
                DateTimeService::getCustomDateTimeObject(
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                )->modify('+' . $appointmentAS->getAppointmentLengthTime($appointment, $service) . ' second')
            )
        );

        $userConnectionChanges = $appointmentAS->getUserConnectionChanges(
            $appointment->getProviderId()->getValue(),
            $oldAppointment->getProviderId()->getValue()
        );

        if ($oldAppointment->getZoomMeeting()) {
            $appointment->setZoomMeeting($oldAppointment->getZoomMeeting());
        }

        if (
            $bookingAS->isBookingApprovedOrPending($appointment->getStatus()->getValue()) &&
            $bookingAS->isBookingCanceledOrRejectedOrNoShow($oldAppointment->getStatus()->getValue())
        ) {
            /** @var AbstractUser $user */
            $user = $this->container->get('logged.in.user');

            if (!$appointmentAS->canBeBooked($appointment, $userAS->isCustomer($user), null, null)) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
                $result->setData(
                    [
                        'timeSlotUnavailable' => true
                    ]
                );

                return $result;
            }
        }

        $appointment->setGoogleCalendarEventId($oldAppointment->getGoogleCalendarEventId());
        $appointment->setGoogleMeetUrl($oldAppointment->getGoogleMeetUrl());
        $appointment->setOutlookCalendarEventId($oldAppointment->getOutlookCalendarEventId());
        $appointment->setMicrosoftTeamsUrl($oldAppointment->getMicrosoftTeamsUrl());
        $appointment->setAppleCalendarEventId($oldAppointment->getAppleCalendarEventId());

        $appointmentRepo->beginTransaction();

        /** @var Collection $removedBookings */
        $removedBookings = new Collection();

        foreach ($removedBookingsData as $removedBookingData) {
            $removedBookings->addItem(CustomerBookingFactory::create($removedBookingData), $removedBookingData['id']);
        }

        $paymentData = [];

        $bookingAdded = $bookingAS->isBookingAdded($appointment->getBookings());

        /** @var CustomerBooking $oldBooking */
        foreach ($oldAppointment->getBookings()->getItems() as $oldBooking) {
            if (
                $appointment->getServiceId()->getValue() !== $oldAppointment->getServiceId()->getValue() &&
                !$appointmentAS->processPackageAppointmentBooking(
                    $oldBooking,
                    $removedBookings,
                    $appointment->getServiceId()->getValue(),
                    $paymentData
                )
            ) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage(FrontendStrings::getCommonStrings()['package_booking_unavailable']);
                $result->setData(
                    [
                        'packageBookingUnavailable' => true
                    ]
                );

                return $result;
            }
        }

        $paymentData = !empty($command->getField('payment')) ? array_merge($command->getField('payment'), ['isBackendBooking' => true]) :
            ['amount' => 0, 'gateway' => 'onSite', 'isBackendBooking' => true];

        try {
            $appointmentAS->update(
                $oldAppointment,
                $appointment,
                $removedBookings,
                $service,
                $paymentData
            );
        } catch (QueryExecutionException $e) {
            $appointmentRepo->rollback();
            throw $e;
        }

        $appointmentRepo->commit();

        do_action(
            'amelia_after_appointment_updated',
            $appointment ? $appointment->toArray() : null,
            $oldAppointment ? $oldAppointment->toArray() : null,
            $removedBookings ? $removedBookings->toArray() : null,
            $service ? $service->toArray() : null,
            $paymentData
        );


        $appointmentStatusChanged = $appointmentAS->isAppointmentStatusChanged($appointment, $oldAppointment);

        $appRescheduled = $appointmentAS->isAppointmentRescheduled($appointment, $oldAppointment);

        if ($appRescheduled) {
            if (!$appointmentAS->canBeBooked($appointment, false, null, null)) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage(FrontendStrings::getCommonStrings()['package_booking_unavailable']);
                $result->setData(
                    [
                        'timeSlotUnavailable' => true
                    ]
                );

                return $result;
            }

            $appointment->setRescheduled(new BooleanValueObject(true));

            foreach ($appointment->getBookings()->getItems() as $booking) {
                $bookingStartInUtc = DateTimeService::getCustomDateTimeObjectInUtc(
                    $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                )->format('Y-m-d H:i:s');

                $paymentAS->updateBookingPaymentDate(
                    $booking,
                    $bookingStartInUtc
                );

                if ($appointmentAS->isPeriodCustomPricing($service)) {
                    /** @var UserRepository $userRepository */
                    $userRepository = $this->getContainer()->get('domain.users.repository');

                    /** @var CustomerBookingRepository $bookingRepository */
                    $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

                    /** @var Provider $provider */
                    $provider = $userRepository->getById($appointment->getProviderId()->getValue());

                    $price = $appointmentAS->getBookingPriceForService(
                        $service,
                        null,
                        $provider,
                        $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                    );

                    $booking->setPrice(new Price($price));

                    $bookingRepository->updatePrice($booking->getId()->getValue(), $booking);
                }
            }

            $bookingAS->bookingRescheduled(
                $oldAppointment->getId()->getValue(),
                Entities::APPOINTMENT,
                null,
                Entities::CUSTOMER
            );

            $bookingAS->bookingRescheduled(
                $oldAppointment->getId()->getValue(),
                Entities::APPOINTMENT,
                $oldAppointment->getProviderId()->getValue(),
                Entities::PROVIDER
            );
        }

        if ($appointmentStatusChanged) {
            $appointmentStatus = $appointment->getStatus()->getValue();

            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if (
                    $booking->getStatus()->getValue() === BookingStatus::APPROVED &&
                    ($appointmentStatus === BookingStatus::PENDING || $appointmentStatus === BookingStatus::APPROVED)
                ) {
                    $booking->setChangedStatus(new BooleanValueObject(true));
                }
            }

            $appointment->setChangedStatus(new BooleanValueObject(true));
        }

        $appointmentArray = $appointment->toArray();

        $oldAppointmentArray = $oldAppointment->toArray();

        $bookingsWithChangedStatus = $bookingAS->getBookingsWithChangedStatus($appointmentArray, $oldAppointmentArray);

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            $reservationService->updateWooCommerceOrder($booking, $appointment);
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated appointment');
        $result->setData(
            [
                Entities::APPOINTMENT          => $appointmentArray,
                'appointmentStatusChanged'     => $appointmentStatusChanged,
                'oldAppointmentStatus'         => $oldAppointment->getStatus()->getValue(),
                'appointmentRescheduled'       => $appRescheduled,
                'bookingsWithChangedStatus'    => $bookingsWithChangedStatus,
                'appointmentEmployeeChanged'   => $userConnectionChanges['appointmentEmployeeChanged'],
                'appointmentZoomUserChanged'   => $userConnectionChanges['appointmentZoomUserChanged'],
                'bookingAdded'                 => $bookingAdded,
                'appointmentZoomUsersLicenced' => $userConnectionChanges['appointmentZoomUsersLicenced']
            ]
        );

        $customFieldService->deleteUploadedFilesForDeletedBookings(
            $appointment->getBookings(),
            $oldAppointment->getBookings()
        );

        return $result;
    }
}
