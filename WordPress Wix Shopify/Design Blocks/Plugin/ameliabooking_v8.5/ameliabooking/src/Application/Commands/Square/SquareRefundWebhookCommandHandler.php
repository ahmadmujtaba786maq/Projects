<?php

namespace AmeliaBooking\Application\Commands\Square;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Services\Payment\CurrencyService;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use Interop\Container\Exception\ContainerException;

/**
 * Class SquareRefundWebhookCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Square
 */
class SquareRefundWebhookCommandHandler extends CommandHandler
{
    /**
     * @param SquareRefundWebhookCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function handle(SquareRefundWebhookCommand $command)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $result = new CommandResult();

        $data = $command->getField('data');

        if ($data && !empty($data['object']['refund']['payment_id'])) {
            $payments = $paymentRepository->getByEntityId($data['object']['refund']['payment_id'], 'transactionId');

            if ($payments->length() === 0) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Cannot find payment');
                $result->setData(['success' => false]);

                return $result;
            }

            foreach ($payments->toArray() as $payment) {
//                if (floatval($payment['amount']) <= floatval($data['object']['refund']['amount_money']['amount']/100)) {
                    $paymentRepository->updateFieldById($payment['id'], 'refunded', 'status');
//                }
            }
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated payment status');
        $result->setData(['success' => true]);

        return $result;
    }
}
