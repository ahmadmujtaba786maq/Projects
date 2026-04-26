<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;

/**
 * Class PayPalPaymentCallbackCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class PayPalPaymentCallbackCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'status',
        'token',
        'PayerID',
    ];

    /**
     * @param PayPalPaymentCallbackCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function handle(PayPalPaymentCallbackCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('');
        $result->setData([]);

        return $result;
    }
}
