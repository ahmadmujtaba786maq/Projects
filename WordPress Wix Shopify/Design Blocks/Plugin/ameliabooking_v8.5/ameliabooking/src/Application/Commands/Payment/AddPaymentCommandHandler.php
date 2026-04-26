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
 * Class AddPaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class AddPaymentCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [];

    /**
     * @param AddPaymentCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(AddPaymentCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to add new payment.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $paymentArray = $command->getFields();

        $paymentArray = apply_filters('amelia_before_payment_added_filter', $paymentArray);

        do_action('amelia_before_payment_added', $paymentArray);

        $payment = PaymentFactory::create($paymentArray);
        if (!$payment instanceof Payment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to create payment.');

            return $result;
        }

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        if ($paymentId = $paymentRepository->add($payment)) {
            $payment->setId(new Id($paymentId));

            do_action('amelia_after_payment_added', $payment->toArray());

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('New payment successfully created.');
            $result->setData(
                [
                    Entities::PAYMENT => $payment->toArray(),
                ]
            );
        }

        return $result;
    }
}
