<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class AddAppointmentCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'bookings',
        'bookingStart',
        'notifyParticipants',
        'serviceId',
        'providerId'
    ];

    /**
     * @param AddAppointmentCommand $command
     *
     * @return CommandResult
     * @throws NotFoundException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(AddAppointmentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');
        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

        if ($missingEntity = $entityService->getMissingEntityForAppointment($command->getFields())) {
            return $entityService->getMissingEntityResponse($missingEntity);
        }

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
            throw new AccessDeniedException('You are not allowed to add an appointment');
        }

        $appointmentData = $command->getFields();

        $paymentData = !empty($command->getField('payment')) ? array_merge($command->getField('payment'), ['isBackendBooking' => true]) :
            ['amount' => 0, 'gateway' => 'onSite', 'isBackendBooking' => true];

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService($appointmentData['serviceId'], $appointmentData['providerId']);

        $appointmentData = apply_filters('amelia_before_appointment_added_filter', $appointmentData, $service ? $service->toArray() : null, $paymentData);

        do_action('amelia_before_appointment_added', $appointmentData, $service ? $service->toArray() : null, $paymentData);

        $maxDuration = 0;

        foreach ($appointmentData['bookings'] as $booking) {
            if ($booking['duration'] > $maxDuration && ($booking['status'] === BookingStatus::APPROVED || BookingStatus::PENDING)) {
                $maxDuration = $booking['duration'];
            }
        }

        if ($maxDuration) {
            $service->setDuration(new PositiveDuration($maxDuration));
        }

        $appointmentAS->convertTime($appointmentData);

        $reservationService->manageTaxes($appointmentData);

        $appointmentRepo->beginTransaction();

        $ignoredData = [];

        /** @var Appointment $existingAppointment */
        $existingAppointment = $appointmentAS->getAlreadyBookedAppointment($appointmentData, false, $service);

        /** @var Appointment $appointment */
        $appointment = $appointmentAS->build(
            $existingAppointment ? $existingAppointment->toArray() : $appointmentData,
            $service
        );

        if ($existingAppointment && !empty($appointmentData['internalNotes'])) {
            if (
                $existingAppointment->getInternalNotes() &&
                $existingAppointment->getInternalNotes()->getValue()
            ) {
                $appointment->setInternalNotes(
                    new Description(
                        $existingAppointment->getInternalNotes()->getValue() .
                        PHP_EOL .
                        PHP_EOL .
                        $appointmentData['internalNotes']
                    )
                );
            } else {
                $appointment->setInternalNotes(
                    new Description(
                        $appointmentData['internalNotes']
                    )
                );
            }
        }

        try {
            $appointmentAS->addOrEditAppointment(
                $appointment,
                $existingAppointment,
                $service,
                $appointmentData,
                $paymentData
            );
        } catch (CustomerBookedException $e) {
            $appointmentRepo->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(
                [
                    'customerAlreadyBooked' => true
                ]
            );

            return $result;
        } catch (BookingUnavailableException $e) {
            $appointmentRepo->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(
                [
                    'timeSlotUnavailable' => true
                ]
            );

            return $result;
        }

        foreach ($appointmentData['bookings'] as $bookingData) {
            $paymentData['customerPaymentParentId'][(int)$bookingData['customerId']] = null;
        }

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if (
                $booking->getCustomerId() &&
                $booking->getCustomerId()->getValue() &&
                array_key_exists($booking->getCustomerId()->getValue(), $paymentData['customerPaymentParentId']) &&
                $booking->getPayments() &&
                $booking->getPayments()->keyExists(0)
            ) {
                /** @var Payment $payment */
                $payment = $booking->getPayments()->getItem(0);

                $paymentData['customerPaymentParentId'][$booking->getCustomerId()->getValue()]
                    = $payment->getId()->getValue();
            }
        }

        if ($existingAppointment !== null) {
            $existingAppointmentId = $existingAppointment->getId()->getValue();

            $ignoredData[$existingAppointmentId] = [
                'status'      => $existingAppointment->getStatus()->getValue(),
                'bookingsIds' => [],
            ];

            /** @var CustomerBooking $booking */
            foreach ($existingAppointment->getBookings()->getItems() as $booking) {
                $ignoredData[$existingAppointmentId]['bookingsIds'][$booking->getId()->getValue()] = true;
            }
        }

        $error = false;

        $recurringAppointments = [];

        foreach ($command->getField('recurring') as $recurringData) {
            $recurringAppointmentData = array_merge(
                $appointmentData,
                [
                    'bookingStart' => $recurringData['bookingStart'],
                    'locationId'   => $recurringData['locationId'],
                    'parentId'     => $appointment->getId()->getValue()
                ]
            );

            $appointmentAS->convertTime($recurringAppointmentData);

            /** @var Appointment $existingRecurringAppointment */
            $existingRecurringAppointment = $appointmentAS->getAlreadyBookedAppointment(
                $recurringAppointmentData,
                false,
                $service
            );

            /** @var Appointment $recurringAppointment */
            $recurringAppointment = $appointmentAS->build(
                $existingRecurringAppointment ? $existingRecurringAppointment->toArray() : $recurringAppointmentData,
                $service
            );

            if ($existingRecurringAppointment && $recurringAppointmentData['internalNotes']) {
                if (
                    $existingRecurringAppointment->getInternalNotes() &&
                    $existingRecurringAppointment->getInternalNotes()->getValue()
                ) {
                    $recurringAppointment->setInternalNotes(
                        new Description(
                            $existingRecurringAppointment->getInternalNotes()->getValue() .
                            PHP_EOL .
                            PHP_EOL .
                            $recurringAppointmentData['internalNotes']
                        )
                    );
                } else {
                    $recurringAppointment->setInternalNotes(
                        new Description(
                            $recurringAppointmentData['internalNotes']
                        )
                    );
                }
            }

            try {
                $appointmentAS->addOrEditAppointment(
                    $recurringAppointment,
                    $existingRecurringAppointment,
                    $service,
                    $recurringAppointmentData,
                    $paymentData
                );
            } catch (CustomerBookedException $e) {
                $appointmentRepo->rollback();

                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());
                $result->setData(
                    [
                        'customerAlreadyBooked' => true
                    ]
                );

                $error = true;
            } catch (BookingUnavailableException $e) {
                $appointmentRepo->rollback();

                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());
                $result->setData(
                    [
                        'timeSlotUnavailable' => true
                    ]
                );

                $error = true;
            }

            if ($error) {
                $appointmentAS->delete($appointment, $ignoredData);

                if (
                    $appointment->getId() &&
                    $appointment->getId()->getValue() &&
                    !empty($ignoredData[$appointment->getId()->getValue()])
                ) {
                    $appointmentRepo->updateFieldById(
                        $appointment->getId()->getValue(),
                        $ignoredData[$appointment->getId()->getValue()]['status'],
                        'status'
                    );
                }

                foreach ($recurringAppointments as $savedRecurringAppointment) {
                    $appointmentAS->delete(
                        $appointmentAS->build($savedRecurringAppointment[Entities::APPOINTMENT], $service),
                        $ignoredData
                    );

                    if (!empty($ignoredData[$savedRecurringAppointment[Entities::APPOINTMENT]['id']])) {
                        $appointmentRepo->updateFieldById(
                            $savedRecurringAppointment[Entities::APPOINTMENT]['id'],
                            $ignoredData[$savedRecurringAppointment[Entities::APPOINTMENT]['id']]['status'],
                            'status'
                        );
                    }
                }

                return $result;
            }

            if ($existingRecurringAppointment !== null) {
                $existingAppointmentId = $existingRecurringAppointment->getId()->getValue();

                $ignoredData[$existingAppointmentId] = [
                    'status'      => $existingRecurringAppointment->getStatus()->getValue(),
                    'bookingsIds' => [],
                ];

                /** @var CustomerBooking $booking */
                foreach ($existingRecurringAppointment->getBookings()->getItems() as $booking) {
                    $ignoredData[$existingAppointmentId]['bookingsIds'][$booking->getId()->getValue()] = true;
                }
            }

            $recurringAppointments[] = [
                Entities::APPOINTMENT => $recurringAppointment->toArray()
            ];
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new appointment');
        $result->setData(
            [
                Entities::APPOINTMENT => $appointment->toArray(),
                'recurring'           => $recurringAppointments
            ]
        );

        $appointmentRepo->commit();

        do_action('amelia_after_appointment_added', $appointment ? $appointment->toArray() : null, $service ? $service->toArray() : null, $paymentData);

        return $result;
    }
}
