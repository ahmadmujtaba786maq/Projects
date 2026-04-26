<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Services\LessonSpace\AbstractLessonSpaceService;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetAppointmentCommandHandler extends CommandHandler
{
    /**
     * @param GetAppointmentCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function handle(GetAppointmentCommand $command)
    {
        $result = new CommandResult();

        /** @var UserApplicationService $userAS */
        $userAS = $this->container->get('application.user.service');

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

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepo->getById((int)$command->getField('id'));

        if ($userAS->isCustomer($user) && !$customerAS->hasCustomerBooking($appointment->getBookings(), $user)) {
            throw new AccessDeniedException('You are not allowed to read appointment');
        }

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $bookingIds = [];

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            /** @var Payment $payment */
            foreach ($booking->getPayments()->getItems() as $payment) {
                if ($payment->getParentId() && $payment->getParentId()->getValue()) {
                    try {
                        /** @var Payment $parentPayment */
                        $parentPayment = $paymentRepository->getById($payment->getParentId()->getValue());

                        $bookingIds[] = $parentPayment->getCustomerBookingId()->getValue();
                    } catch (\Exception $e) {
                    }
                }

                /** @var Collection $relatedPayments */
                $relatedPayments = $paymentRepository->getByEntityId(
                    $payment->getParentId() ? $payment->getParentId()->getValue() : $payment->getId()->getValue(),
                    'parentId'
                );

                /** @var Payment $relatedPayment */
                foreach ($relatedPayments->getItems() as $relatedPayment) {
                    $bookingIds[] = $relatedPayment->getCustomerBookingId()->getValue();
                }
            }
        }

        /** @var Collection $recurringAppointments */
        $recurringAppointments = $bookingIds ? $appointmentRepo->getFiltered(
            [
                'bookingIds' => array_unique($bookingIds),
                'customerId' => !empty($command->getField('params')['customerId']) ? $command->getField('params')['customerId'] : null
            ]
        ) : $appointmentRepo->getFiltered(
            [
                'parentId' => $appointment->getParentId() ?
                    $appointment->getParentId()->getValue() : $appointment->getId()->getValue()
            ]
        );

        if ($recurringAppointments->keyExists($appointment->getId()->getValue())) {
            $recurringAppointments->deleteItem($appointment->getId()->getValue());
        }

        $customerAS->removeBookingsForOtherCustomers($user, new Collection([$appointment]));

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            $customFields = [];

            if (
                $booking->getCustomFields() &&
                ($customFields = json_decode($booking->getCustomFields()->getValue(), true)) === null
            ) {
                $booking->setCustomFields(null);
            }

            if ($customFields) {
                $parsedCustomFields = [];

                foreach ((array)$customFields as $key => $customField) {
                    if ($customField) {
                        $parsedCustomFields[$key] = $customField;
                    }
                }

                $booking->setCustomFields(new Json(json_encode($parsedCustomFields)));
            }
        }

        if (!empty($command->getField('params')['timeZone'])) {
            $appointment->getBookingStart()->getValue()->setTimezone(
                new \DateTimeZone($command->getField('params')['timeZone'])
            );

            $appointment->getBookingEnd()->getValue()->setTimezone(
                new \DateTimeZone($command->getField('params')['timeZone'])
            );
        }

        $appointmentArray = $appointment->toArray();
        if (!empty($appointmentArray['lessonSpace'])) {
            /** @var SettingsService $settingsDS */
            $settingsDS = $this->container->get('domain.settings.service');

            $lessonSpaceApiKey    = $settingsDS->getSetting('lessonSpace', 'apiKey');
            $lessonSpaceEnabled   = $settingsDS->getSetting('lessonSpace', 'enabled');
            $lessonSpaceCompanyId = $settingsDS->getSetting('lessonSpace', 'companyId');
            if ($lessonSpaceEnabled && $lessonSpaceApiKey && $lessonSpaceCompanyId) {
                /** @var AbstractLessonSpaceService $lessonSpaceService */
                $lessonSpaceService = $this->container->get('infrastructure.lesson.space.service');
                $spaceId            = explode("https://www.thelessonspace.com/space/", $appointmentArray['lessonSpace']);
                if ($spaceId && count($spaceId) > 1) {
                    $appointmentArray['lessonSpaceDetails'] = $lessonSpaceService->getSpace($lessonSpaceApiKey, $lessonSpaceCompanyId, $spaceId[1]);
                }
            }
        }

        if (isset($appointmentArray['notifyParticipants'])) {
            $appointmentArray['notifyParticipants'] = intval($appointmentArray['notifyParticipants']);
        }
        if (isset($appointmentArray['createPaymentLinks'])) {
            $appointmentArray['createPaymentLinks'] = intval($appointmentArray['createPaymentLinks']);
        }

        $appointmentArray = apply_filters('amelia_get_appointment_filter', $appointmentArray);

        do_action('amelia_get_appointment', $appointmentArray);


        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved appointment');
        $result->setData(
            [
                Entities::APPOINTMENT => $appointmentArray,
                'recurring'           => $recurringAppointments->toArray()
            ]
        );

        return $result;
    }
}
