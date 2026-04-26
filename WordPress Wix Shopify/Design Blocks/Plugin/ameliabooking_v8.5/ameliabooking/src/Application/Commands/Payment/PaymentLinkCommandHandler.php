<?php

namespace AmeliaBooking\Application\Commands\Payment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Slim\Exception\ContainerException;

/**
 * Class PaymentLinkCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Payment
 */
class PaymentLinkCommandHandler extends CommandHandler
{
    /**
     * @param PaymentLinkCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     */
    public function handle(PaymentLinkCommand $command)
    {
        $result = new CommandResult();

        /** @var PaymentApplicationService $paymentApplicationService */
        $paymentApplicationService = $this->container->get('application.payment.service');

        $data = $command->getField('data');

        if ($data['data']['type'] === 'appointment') {
            $data['data']['bookable'] = $data['data']['service'];
        } else {
            $data['data']['bookable'] = $data['data'][$data['data']['type']];
        }

        $data['data']['fromPanel'] = true;

        $data = apply_filters('amelia_before_payment_from_panel_created_filter', $data);

        do_action('amelia_before_payment_from_panel_created', $data);

        $paymentLinks = $paymentApplicationService->createPaymentLink(
            $data['data'],
            0,
            null,
            [$data['paymentMethod'] => true],
            $command->getField('redirectUrl')
        );

        $paymentLinks = apply_filters('amelia_after_payment_from_panel_created_filter', $paymentLinks, $data);

        do_action('amelia_after_payment_from_panel_created', $data, $paymentLinks);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage(
            !empty($paymentLinks) && empty($paymentLinks['payment_link_error_message']) && !empty(array_values($paymentLinks)[0])
                ? 'Successfully created link' : $paymentLinks['payment_link_error_message']
        );
        $result->setData(
            [
                'paymentLink' => !empty($paymentLinks['payment_link_error_message']) ? '' : array_values($paymentLinks)[0],
                'error'       => !empty($paymentLinks['payment_link_error_message']) ? $paymentLinks['payment_link_error_message'] : ''
            ]
        );

        return $result;
    }
}
