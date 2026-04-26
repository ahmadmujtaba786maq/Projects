<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Cache\Cache;
use AmeliaBooking\Domain\Services\Payment\PaymentServiceInterface;
use AmeliaBooking\Infrastructure\Repository\Cache\CacheRepository;
use AmeliaBooking\Infrastructure\Services\Payment\MollieService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;
use Exception;
use Interop\Container\Exception\ContainerException;

/**
 * Class MolliePaymentNotifyCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class MolliePaymentNotifyCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'name',
    ];

    /**
     * @param MolliePaymentNotifyCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerException
     */
    public function handle(MolliePaymentNotifyCommand $command)
    {
        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');
        /** @var MollieService $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.mollie.service');
        /** @var CacheRepository $cacheRepository */
        $cacheRepository = $this->container->get('domain.cache.repository');

        $this->checkMandatoryFields($command);

        $response = $paymentService->fetchPayment(
            ['id' => $command->getField('id')]
        );

        $status = $response->getStatus();

        /** @var Cache $cache */
        $cache = ($data = explode('_', $command->getField('name'))) && isset($data[0], $data[1]) ?
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

        return $paymentAS->updateAppointmentAndCache($data[2], $status, $cache, $command->getField('id'));
    }
}
