<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationSMSHistoryRepository;

/**
 * Class UpdateSMSNotificationHistoryCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class UpdateSMSNotificationHistoryCommandHandler extends CommandHandler
{
    /**
     * @param UpdateSMSNotificationHistoryCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateSMSNotificationHistoryCommand $command)
    {
        $result = new CommandResult();

        /** @var NotificationSMSHistoryRepository $notificationsSMSHistoryRepo */
        $notificationsSMSHistoryRepo = $this->container->get('domain.notificationSMSHistory.repository');

        $updateData = [
            'status'   => $command->getField('status'),
            'price'    => $command->getField('price'),
            'logId'    => $command->getField('logId'),
            'dateTime' => $command->getField('dateTime')
        ];

        $updateData = apply_filters('amelia_before_sms_notification_history_updated_filter', $updateData, $command->getArg('id'));

        do_action('amelia_before_sms_notification_history_updated', $updateData, $command->getArg('id'));

        if ($notificationsSMSHistoryRepo->update((int)$command->getArg('id'), $updateData)) {
            do_action('amelia_after_sms_notification_history_updated', $updateData, $command->getArg('id'));

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated SMS notification history.');
        }

        return $result;
    }
}
