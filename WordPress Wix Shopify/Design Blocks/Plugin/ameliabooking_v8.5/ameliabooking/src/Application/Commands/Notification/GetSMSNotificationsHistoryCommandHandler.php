<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationSMSHistoryRepository;

/**
 * Class GetSMSNotificationsHistoryCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class GetSMSNotificationsHistoryCommandHandler extends CommandHandler
{
    /**
     * @param GetSMSNotificationsHistoryCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetSMSNotificationsHistoryCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to read notifications');
        }

        $result = new CommandResult();

        /** @var NotificationSMSHistoryRepository $notificationsSMSHistoryRepo */
        $notificationsSMSHistoryRepo = $this->container->get('domain.notificationSMSHistory.repository');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $itemsPerPage = $settingsService->getSetting('general', 'itemsPerPage');

        $params = $command->getField('params');

        $notifications = $notificationsSMSHistoryRepo->getFiltered($params, $itemsPerPage);

        $notifications = apply_filters('amelia_get_sms_history_filter', $notifications);

        do_action('amelia_get_sms_history', $notifications);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved notifications.');
        $result->setData(
            [
            Entities::NOTIFICATIONS => $notifications,
            'countFiltered'         => (int)$notificationsSMSHistoryRepo->getCount($params)
            ]
        );

        return $result;
    }
}
