<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Payment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\PaymentGateway;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use Interop\Container\Exception\ContainerException;
use AmeliaStripe\PaymentMethod;

/**
 * Class RefundPaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class RefundPaymentCommandHandler extends CommandHandler
{
    /**
     * @param RefundPaymentCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handle(RefundPaymentCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to update payment.');
        }

        $result = new CommandResult();

        $paymentId = $command->getArg('id');

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var Payment $payment */
        $payment = $paymentRepository->getById($paymentId);

        if (
            $payment->getAmount()->getValue() === 0.0 ||
            $payment->getGateway()->getName()->getValue() === PaymentType::ON_SITE ||
            $payment->getStatus()->getValue() === PaymentStatus::REFUNDED
        ) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Payment object can not be refunded');
            $result->setData(
                [
                    Entities::PAYMENT => $payment->toArray(),
                ]
            );

            return $result;
        }

        $amount =
            $paymentAS->hasRelatedRefundablePayment($payment) ||
            $payment->getGateway()->getName()->getValue() === PaymentType::SQUARE ?
                $payment->getAmount()->getValue() :
                null;

        do_action('amelia_before_payment_refunded', $payment->toArray(), $amount);

        if ($payment->getGateway()->getName()->getValue() === PaymentType::WC) {
            $response = WooCommerceService::refund(
                $payment->getWcOrderId()->getValue(),
                $payment->getWcOrderItemId() ? $payment->getWcOrderItemId()->getValue() : null,
                $amount
            );
        } else {
            /** @var PaymentServiceInterface $paymentService */
            $paymentService = $this->container->get(
                'infrastructure.payment.' . $payment->getGateway()->getName()->getValue() . '.service'
            );

            $response = $paymentService->refund(
                [
                    'id'        => $payment->getTransactionId(),
                    'transfers' => $payment->getTransfers()
                        ? json_decode($payment->getTransfers()->getValue(), true)
                        : null,
                    'amount'    => $amount,
                ]
            );
        }

        if (!empty($response['error'])) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($response['error']);
            $result->setData(
                [
                    Entities::PAYMENT => $payment->toArray(),
                    'response'        => $response
                ]
            );

            return $result;
        }

        $paymentRepository->updateFieldById(
            $payment->getId()->getValue(),
            PaymentStatus::REFUNDED,
            'status'
        );

        do_action('amelia_after_payment_refunded', $payment->toArray(), $amount);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Payment successfully refunded.');
        $result->setData(
            [
                Entities::PAYMENT => $payment->toArray(),
                'response'        => $response
            ]
        );

        return $result;
    }
}
