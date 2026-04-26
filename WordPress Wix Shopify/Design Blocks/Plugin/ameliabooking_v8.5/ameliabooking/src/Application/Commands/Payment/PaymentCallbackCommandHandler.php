<?php

namespace AmeliaBooking\Application\Commands\Payment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Payment\PaymentGateway;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Factory\Stripe\StripeFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Payment\AbstractPaymentService;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;
use AmeliaBooking\Infrastructure\Services\Payment\MollieService;
use AmeliaBooking\Infrastructure\Services\Payment\PayPalService;
use AmeliaBooking\Infrastructure\Services\Payment\RazorpayService;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use AmeliaBooking\Infrastructure\Services\Payment\StripeService;
use Interop\Container\Exception\ContainerException;

/**
 * Class PaymentCallbackCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class PaymentCallbackCommandHandler extends CommandHandler
{
    /**
     * @param PaymentCallbackCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function handle(PaymentCallbackCommand $command)
    {
        $result = new CommandResult();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        $paymentId = $command->getField('paymentAmeliaId');

        $gateway = $command->getField('paymentMethod');

        $redirectLink = '';

        $payment = null;

        if ($paymentId) {
            /** @var Payment $payment */
            $payment = $paymentRepository->getById($paymentId);

            $paymentArray = apply_filters('amelia_before_payment_link_callback_filter', $payment ? $payment->toArray() : null, $command->getFields());

            $payment = PaymentFactory::create($paymentArray);

            /** @var ReservationServiceInterface $reservationService */
            $reservationService = $this->container->get('application.reservation.service')->get(
                $payment->getEntity()->getValue()
            );
            $reservation        = $reservationService->getReservationByPayment($payment, true);
            $data = $reservation->getData();

            $bookableSettings     = $data['bookable']['settings'];
            $entitySettings       = !empty($bookableSettings) && json_decode($bookableSettings, true) ? json_decode($bookableSettings, true) : null;
            $paymentLinksSettings =
                !empty($entitySettings) && !empty($entitySettings['payments']['paymentLinks']) ? $entitySettings['payments']['paymentLinks'] : null;
            $redirectUrl          = $paymentLinksSettings && $paymentLinksSettings['redirectUrl'] ? $paymentLinksSettings['redirectUrl'] :
                $settingsDS->getSetting('payments', 'paymentLinks')['redirectUrl'];
            $redirectLink         = empty($redirectUrl) ? AMELIA_SITE_URL : $redirectUrl;
            $customerPanelUrl     = $settingsDS->getSetting('roles', 'customerCabinet')['pageUrl'];
            $redirectLink         = !empty($command->getField('fromPanel')) ? $customerPanelUrl : $redirectLink;

            $changeBookingStatus =
                $paymentLinksSettings && $paymentLinksSettings['changeBookingStatus'] !== null ? $paymentLinksSettings['changeBookingStatus'] :
                $settingsDS->getSetting('payments', 'paymentLinks')['changeBookingStatus'];


            $transactionId = null;
            $paymentRepository->beginTransaction();

            do_action('amelia_before_payment_link_callback', $payment ? $payment->toArray() : null, $command->getFields());

            try {
                $status = PaymentStatus::PAID;
                if ($gateway) {
                    /** @var RazorpayService|MollieService|StripeService|PayPalService|SquareService $paymentService */
                    $paymentService = $this->container->get('infrastructure.payment.' . $gateway . '.service');

                    switch ($gateway) {
                        case 'razorpay':
                            $attributes = array(
                                'razorpay_payment_link_id' => $command->getField('razorpay_payment_link_id'),
                                'razorpay_payment_id'      => $command->getField('razorpay_payment_id'),
                                'razorpay_payment_link_reference_id'  => $command->getField('razorpay_payment_link_reference_id'),
                                'razorpay_payment_link_status'  => $command->getField('razorpay_payment_link_status'),
                                'razorpay_signature'        => $command->getField('razorpay_signature'),
                            );

                            $paymentService->verify($attributes);
                            $transactionId = $attributes['razorpay_payment_id'];
                            break;
                        case ('payPal'):
                            $response = $paymentService->complete(
                                [
                                    'transactionReference' => $command->getField('paymentId'),
                                    'PayerID'              => $command->getField('PayerID'),
                                    'amount'               => $command->getField('chargedAmount'),
                                ]
                            );

                            if (!$response->isSuccessful() || $command->getField('payPalStatus') === 'canceled') {
                                $result->setResult(CommandResult::RESULT_SUCCESS);
                                $result->setMessage('');
                                $result->setData([]);
                                $result->setUrl($redirectLink . '&status=canceled');


                                return $result;
                            }
                            $transactionId = $response->getData()['id'];
                            break;

                        case 'mollie':
                            $response = $paymentService->fetchPaymentLink(
                                $command->getField('id')
                            );

                            $status = !empty($response['paidAt']) ? 'paid' : null;

                            $transactionId = $command->getField('id');
                            break;

                        case 'stripe':
                            $sessionId = $command->getField('session_id');

                            $accountId = $command->getField('accountId');

                            $method = $command->getField('method');

                            if ($sessionId) {
                                $paymentData = $paymentService->getPaymentIntent($sessionId, $method, $accountId);
                                if (!empty($paymentData['payment_intent'])) {
                                    $transactionId = $paymentData['payment_intent'];
                                }
                                if (!empty($paymentData['customer']) && $reservation->getData() && !empty($reservation->getData()['customer']['id'])) {
                                    /** @var CustomerRepository $customerRepository */
                                    $customerRepository = $this->container->get('domain.users.customers.repository');

                                    $stripeConnect = StripeFactory::create(['id' => $paymentData['customer']]);
                                    if (empty($reservation->getData()['customer']['stripeConnect'])) {
                                        $customerRepository->updateFieldById(
                                            $reservation->getData()['customer']['id'],
                                            json_encode($stripeConnect->toArray()),
                                            'stripeConnect'
                                        );
                                    }
                                }
                            }
                            break;

                        case 'square':
                            /** @var SquareService $paymentService */
                            $paymentService = $this->container->get('infrastructure.payment.square.service');

                            if ($command->getField('squareOrderId')) {
                                $response = $paymentService->getOrderResponse($command->getField('squareOrderId'));
                                $order    = $response->isSuccess() ? $response->getResult()->getOrder() : null;
                                if ($order && $order->getTenders() && sizeof($order->getTenders()) > 0) {
                                    $transactionId = $order->getTenders()[0]->getPaymentId();
                                    $response      = $paymentService->completePayment($transactionId);
                                }
                            }
                            break;
                    }

                    if ($status === 'paid') {
                        $amount = $command->getField('chargedAmount');

                        if ($payment->getStatus()->getValue() !== PaymentStatus::PENDING) {
                            $payment->setStatus(new PaymentStatus(PaymentStatus::PAID));
                            $payment->setGateway(new PaymentGateway(new Name($gateway)));
                            if ($transactionId) {
                                $payment->setTransactionId($transactionId);
                            }
                            $linkPayment = $paymentAS->insertPaymentFromLink($payment->toArray(), $amount, $payment->getEntity()->getValue());
                            $paymentId   = $linkPayment->getId()->getValue();
                        } else {
                            $paymentRepository->updateFieldById($paymentId, $amount, 'amount');
                            $paymentRepository->updateFieldById($paymentId, $gateway, 'gateway');
                            $paymentRepository->updateFieldById($paymentId, $status, 'status');
                            $paymentRepository->updateFieldById($paymentId, $transactionId, 'transactionId');
                            $paymentRepository->updateFieldById($paymentId, DateTimeService::getNowDateTimeObjectInUtc()->format('Y-m-d H:i:s'), 'dateTime');
                        }

                        if (!empty($method) && !empty($accountId)) {
                            $paymentRepository->updateFieldById(
                                $paymentId,
                                json_encode(['method' => $method, 'accounts' => [$accountId => []]]),
                                'transfers'
                            );
                        }

                        if ($payment->getEntity()->getValue() === Entities::APPOINTMENT) {
                            if ($changeBookingStatus && $data['booking']['status'] !== BookingStatus::APPROVED) {
                                $appointmentAS->approveBooking($data['booking']['id']);
                            }
                        }
                    }
                }
            } catch (QueryExecutionException $e) {
                $paymentRepository->rollback();
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());

                return $result;
            } catch (ContainerException $e) {
                $paymentRepository->rollback();
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());

                return $result;
            } catch (\Exception $e) {
                $paymentRepository->rollback();
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());

                return $result;
            }
        }

        $paymentRepository->commit();

        if ($payment) {
            do_action('amelia_after_payment_link_callback', $payment->toArray(), $command->getFields());
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('');
        $result->setData([]);
        if ($gateway !== 'mollie' && $redirectLink) {
            $result->setUrl($redirectLink);
        }

        return $result;
    }
}
