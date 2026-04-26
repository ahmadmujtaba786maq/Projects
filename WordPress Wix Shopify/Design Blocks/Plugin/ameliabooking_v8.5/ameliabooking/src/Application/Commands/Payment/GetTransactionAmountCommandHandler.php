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
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\WP\Integrations\WooCommerce\WooCommerceService;
use Exception;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetTransactionAmountCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class GetTransactionAmountCommandHandler extends CommandHandler
{
    /**
     * @param GetTransactionAmountCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function handle(GetTransactionAmountCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to read payment.');
        }

        $result = new CommandResult();

        $paymentId = $command->getArg('id');

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var Payment $payment */
        $payment = $paymentRepository->getById($paymentId);

        if (empty($payment->getTransactionId()) && empty($payment->getWcOrderId())) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Payment has no transaction id');
            $result->setData(
                [
                    Entities::PAYMENT => $payment->toArray(),
                ]
            );

            return $result;
        }


        if ($payment->getGateway()->getName()->getValue() === PaymentType::WC && $payment->getWcOrderId()) {
            $amount = WooCommerceService::getOrderAmount($payment->getWcOrderId()->getValue());
        } else {
            /** @var PaymentServiceInterface $paymentService */
            $paymentService = $this->container->get(
                'infrastructure.payment.' . $payment->getGateway()->getName()->getValue() . '.service'
            );

            $amount = $paymentService->getTransactionAmount(
                $payment->getTransactionId(),
                $payment->getTransfers() ? json_decode($payment->getTransfers()->getValue(), true) : null
            );
        }

        $amount = apply_filters('amelia_get_transaction_amount_filter', $amount, $payment ? $payment->toArray() : null);

        do_action('amelia_get_transaction_amount', $amount, $payment ? $payment->toArray() : null);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Retrieved transaction successfully.');
        $result->setData(
            [
                Entities::PAYMENT   => $payment->toArray(),
                'transactionAmount' => $amount,
                'refundAmount'      => $paymentAS->hasRelatedRefundablePayment($payment) ?
                    $payment->getAmount()->getValue() : $amount,
            ]
        );

        return $result;
    }
}
