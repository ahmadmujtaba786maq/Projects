<?php

namespace AmeliaBooking\Infrastructure\WP\EventListeners\User\Provider;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;

/**
 * Class ProviderAddedEventHandler
 *
 * @package AmeliaBooking\Infrastructure\WP\EventListeners\User\Provider
 */
class ProviderAddedEventHandler
{
    /**
     * @param CommandResult $commandResult
     * @param Container $container
     *
     * @throws ContainerException
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public static function handle($commandResult, $container)
    {
        if (!is_string($commandResult->getData()) && $commandResult->getData()['sendEmployeePanelAccessEmail'] === true) {
            /** @var EmailNotificationService $emailNotificationService */
            $emailNotificationService = $container->get('application.emailNotification.service');
            /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
            $whatsAppNotificationService = $container->get('application.whatsAppNotification.service');

            $emailNotificationService->sendEmployeePanelAccess(
                $commandResult->getData()['user'],
                $commandResult->getData()['password']
            );

            if (
                !empty($commandResult->getData()['user']) &&
                $whatsAppNotificationService->checkRequiredFields() &&
                !empty($commandResult->getData()['user']['phone'])
            ) {
                $whatsAppNotificationService->sendEmployeePanelAccess(
                    $commandResult->getData()['user'],
                    $commandResult->getData()['password']
                );
            }
        }
    }
}
