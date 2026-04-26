<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Payment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;

/**
 * Class UpdatePaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class UpdatePaymentCommandHandler extends CommandHandler
{
    /**
     * @param UpdatePaymentCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdatePaymentCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to update payment.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $paymentArray = $command->getFields();

        $paymentArray = apply_filters('amelia_before_payment_updated_filter', $paymentArray);

        do_action('amelia_before_payment_updated', $paymentArray);

        $payment = PaymentFactory::create($paymentArray);

        if (!$payment instanceof Payment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to update payment.');

            return $result;
        }

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $paymentId = (int)$command->getArg('id');
        if ($paymentRepository->update($paymentId, $payment)) {
            $payment->setId(new Id($paymentId));
            do_action('amelia_after_payment_updated', $payment->toArray());

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Payment successfully updated.');
            $result->setData(
                [
                    Entities::PAYMENT => $payment->toArray(),
                ]
            );
        }

        return $result;
    }
}
