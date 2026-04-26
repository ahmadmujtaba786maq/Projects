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
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetPaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class GetPaymentCommandHandler extends CommandHandler
{
    /**
     * @param GetPaymentCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(GetPaymentCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::FINANCE)) {
            throw new AccessDeniedException('You are not allowed to read payment.');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var Payment $payment */
        $payment = $paymentRepository->getById($command->getArg('id'));

        $paymentArray = apply_filters('amelia_get_payment_filter', $payment->toArray());

        do_action('amelia_get_payment', $paymentArray);


        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved payment.');
        $result->setData(
            [
                Entities::PAYMENT => $paymentArray,
            ]
        );

        return $result;
    }
}
