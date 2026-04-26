<?php

namespace AmeliaBooking\Application\Commands\Square;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Cache\Cache;
use AmeliaBooking\Infrastructure\Repository\Cache\CacheRepository;
use AmeliaBooking\Infrastructure\Services\Payment\SquareService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;
use Square\Models\Order;

/**
 * Class SquarePaymentNotifyCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Square
 */
class SquarePaymentNotifyCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'name'
    ];

    /**
     * @param SquarePaymentNotifyCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(SquarePaymentNotifyCommand $command)
    {
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');
        /** @var SquareService $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.square.service');
        /** @var CacheRepository $cacheRepository */
        $cacheRepository = $this->container->get('domain.cache.repository');

        $this->checkMandatoryFields($command);

        $name = $command->getField('name');
        /** @var Cache $cache */
        $cache = ($data = explode('_', $name)) && isset($data[0], $data[1]) ?
            $cacheRepository->getByIdAndName($data[0], $data[1]) : null;

        if (!$cache || !$cache->getPaymentId()) {
            $result = new CommandResult();
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'message' => 'Cache object not saved',
                    'paymentSuccessful' => false,
                ]
            );

            return $result;
        }

        $response = $paymentService->getOrderResponse($command->getField('squareOrderId'));

        if ($response->isError()) {
            $result = new CommandResult();
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData(
                [
                    'message' => $paymentService->getErrorMessage($response),
                    'paymentSuccessful' => false,
                ]
            );

            return $result;
        }

        /**@var Order $order */
        $order = $response->getResult()->getOrder();

        $paymentId = null;
        if ($order && $order->getTenders() && sizeof($order->getTenders()) > 0) {
            $paymentId = $order->getTenders()[0]->getPaymentId();
            $response  = $paymentService->completePayment($paymentId);
        }

        $status = 'paid';

        $result    = $paymentAS->updateAppointmentAndCache($data[2], $status, $cache, $paymentId);
        $returnUrl = urldecode($command->getField('returnUrl'));
        $result->setUrl($returnUrl . (strpos($returnUrl, '?') ? '&' : '?') . 'ameliaCache=' . $name .
            (!empty($command->getField('fragment')) ? '#' . $command->getField('fragment') : ''));
        return $result;
    }
}
