<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\BookingApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Services\Payment\RazorpayService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class RazorpayPaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class RazorpayPaymentCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'bookings',
        'payment'
    ];

    /**
     * @param RazorpayPaymentCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(RazorpayPaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var BookingApplicationService $bookingAS */
        $bookingAS = $this->container->get('application.booking.booking.service');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var RazorpayService $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.razorpay.service');


        /** @var Reservation $reservation */
        $reservation = $reservationService->getNew(true, true, true);

        $reservationService->processBooking(
            $result,
            $bookingAS->getAppointmentData($command->getFields()),
            $reservation,
            false
        );

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }


        $paymentAmount = $reservationService->getReservationPaymentAmount($reservation);

        if (!$paymentAmount) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'paymentSuccessful' => false,
                    'onSitePayment'     => true
                ]
            );

            return $result;
        }

        $additionalInformation = $paymentAS->getBookingInformationForPaymentSettings(
            $reservation,
            PaymentType::RAZORPAY
        );


        $orderData = [
            'amount'  => intval($paymentAmount * 100),
            "notes"   => $additionalInformation['metaData'] ?: [],
        ];

        $orderData = apply_filters('amelia_before_razorpay_execute_filter', $orderData, $reservation->getReservation()->toArray());

        do_action('amelia_before_razorpay_execute', $orderData, $reservation->getReservation()->toArray());

        $transfers = [];

        try {
            $razorpayOrder = $paymentService->execute($orderData, $transfers);
        } catch (\Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'message' => $e->getMessage() && json_decode($e->getMessage(), true) !== false ?
                        json_decode($e->getMessage(), true)['detail'] : '',
                    'paymentSuccessful' => false,
                ]
            );

            return $result;
        }

        $razorpayOrderId = $razorpayOrder['id'];

        $data = [
            "key"               => $paymentService->getKeyId(),
            "amount"            => $orderData['amount'],
            "name"              => $additionalInformation['name'],
            "description"       => $additionalInformation['description'] ?: $reservation->getBookable()->getName()->getValue(),
            "prefill"           => [
                "name"              => $reservation->getCustomer()->getFullName(),
                "email"             => $reservation->getCustomer()->getEmail() ? $reservation->getCustomer()->getEmail()->getValue() : '',
                "contact"           => $reservation->getCustomer()->getPhone() ? $reservation->getCustomer()->getPhone()->getValue() : '',
            ],
            "order_id"          => $razorpayOrderId,
            "notes"             => $additionalInformation['metaData'] ?: [],
        ];

        $data = apply_filters('amelia_after_razorpay_execute_filter', $data, $reservation->getReservation()->toArray());

        do_action('amelia_after_razorpay_execute', $data, $reservation->getReservation()->toArray());

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Proceed to Razorpay Payment Module');
        $result->setData(
            [
                'data' => $data,
            ]
        );

        return $result;
    }
}
