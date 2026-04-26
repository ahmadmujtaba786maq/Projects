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
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use Interop\Container\Exception\ContainerException;

/**
 * Class DeletePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class DeletePaymentCommandHandler extends CommandHandler
{
    /**
     * @param DeletePaymentCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(DeletePaymentCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to delete payments.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var Payment $payment */
        $payment = $paymentRepository->getById($command->getArg('id'));

        $paymentRepository->beginTransaction();

        do_action('amelia_before_payment_deleted', $payment ? $payment->toArray() : null);

        if (!$paymentAS->delete($payment)) {
            $paymentRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete payment.');

            return $result;
        }

        $paymentRepository->commit();

        do_action('amelia_after_payment_deleted', $payment ? $payment->toArray() : null);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Payment successfully deleted.');
        $result->setData(
            [
                Entities::PAYMENT => $payment->toArray()
            ]
        );

        return $result;
    }
}
