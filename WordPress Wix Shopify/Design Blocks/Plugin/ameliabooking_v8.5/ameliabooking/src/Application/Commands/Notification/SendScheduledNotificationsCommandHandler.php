<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Application\Services\Notification\SMSNotificationService;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;

/**
 * Class SendScheduledNotificationsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class SendScheduledNotificationsCommandHandler extends CommandHandler
{
    /**
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function handle()
    {
        $result = new CommandResult();

        /** @var EmailNotificationService $notificationService */
        $notificationService = $this->getContainer()->get('application.emailNotification.service');
        /** @var SMSNotificationService $smsNotificationService */
        $smsNotificationService = $this->getContainer()->get('application.smsNotification.service');
        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->getContainer()->get('application.whatsAppNotification.service');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $notificationService->setSend(false);

        do_action('amelia_before_send_scheduled_notifications');

        $notificationService->sendNextDayReminderNotifications(Entities::APPOINTMENT);
        $notificationService->sendNextDayReminderNotifications(Entities::EVENT);
        $notificationService->sendScheduledNotifications(Entities::APPOINTMENT);
        $notificationService->sendScheduledNotifications(Entities::EVENT);
        $notificationService->sendBirthdayGreetingNotifications();

        if ($settingsService->getSetting('notifications', 'smsSignedIn') === true) {
            $smsNotificationService->sendNextDayReminderNotifications(Entities::APPOINTMENT);
            $smsNotificationService->sendNextDayReminderNotifications(Entities::EVENT);
            $smsNotificationService->sendScheduledNotifications(Entities::APPOINTMENT);
            $smsNotificationService->sendScheduledNotifications(Entities::EVENT);
            $smsNotificationService->sendBirthdayGreetingNotifications();
        }

        if ($whatsAppNotificationService->checkRequiredFields()) {
            $whatsAppNotificationService->sendNextDayReminderNotifications(Entities::APPOINTMENT);
            $whatsAppNotificationService->sendNextDayReminderNotifications(Entities::EVENT);
            $whatsAppNotificationService->sendScheduledNotifications(Entities::APPOINTMENT);
            $whatsAppNotificationService->sendScheduledNotifications(Entities::EVENT);
            $whatsAppNotificationService->sendBirthdayGreetingNotifications();
        }

        $smsNotificationService->sendPreparedNotifications();
        $notificationService->sendPreparedNotifications();

        do_action('amelia_after_send_scheduled_notifications');

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Scheduled email notifications successfully sent');

        return $result;
    }
}
