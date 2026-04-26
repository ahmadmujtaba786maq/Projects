<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Reservation\AbstractReservationService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use Slim\Exception\ContainerValueNotFoundException;
use Exception;

/**
 * Class SuccessfulBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class SuccessfulBookingCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'appointmentStatusChanged',
    ];

    /**
     * @param SuccessfulBookingCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws ContainerValueNotFoundException
     * @throws Exception
     */
    public function handle(SuccessfulBookingCommand $command)
    {
        $this->checkMandatoryFields($command);

        $type = $command->getField('type') === Entities::CART ?
            Entities::APPOINTMENT : $command->getField('type');

        /** @var AbstractReservationService $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $paymentId = $command->getField('paymentId');

        if ($paymentId) {
            /** @var Payment $payment */
            $payment = $paymentRepository->getById($paymentId);

            if (
                ($payment && $payment->getActionsCompleted() && $payment->getActionsCompleted()->getValue()) ||
                ($payment && $payment->getTriggeredActions() && $payment->getTriggeredActions()->getValue())
            ) {
                $result = new CommandResult();

                $result->setResult(CommandResult::RESULT_SUCCESS);
                $result->setMessage('Successfully get booking');
                $result->setDataInResponse(false);

                return $result;
            } elseif ($payment && !$payment->getTriggeredActions()) {
                $paymentRepository->updateFieldById($paymentId, 1, 'triggeredActions');
            }
        }

        $resultData = [
            'bookingId' => (int)$command->getArg('id'),
            'type' => $command->getField('type') ?: Entities::APPOINTMENT,
            'recurring' => !empty($command->getFields()['recurring']) ? $command->getFields()['recurring'] : [],
            'isCart' => $command->getField('type') === Entities::CART,
            'appointmentStatusChanged' => $command->getFields()['appointmentStatusChanged'],
            'packageId' => $command->getField('packageId'),
            'customer' => $command->getField('customer'),
            'paymentId' => $command->getField('paymentId'),
            'packageCustomerId' => $command->getField('packageCustomerId'),
            'isPackageAppointment' => !empty($command->getFields()['isPackageAppointment']),
            'packageBookingFromBackend' => !empty($command->getFields()['packageBookingFromBackend'])
        ];

        $resultData = apply_filters('amelia_before_post_booking_actions_filter', $resultData);

        do_action('amelia_before_post_booking_actions', $resultData);


        return $reservationService->getSuccessBookingResponse(
            $resultData['bookingId'],
            $resultData['type'],
            $resultData['recurring'],
            $resultData['isCart'],
            $resultData['appointmentStatusChanged'],
            $resultData['packageId'],
            $resultData['customer'],
            $resultData['paymentId'],
            $resultData['packageCustomerId'],
            $resultData['isPackageAppointment'],
            $resultData['packageBookingFromBackend']
        );
    }
}
