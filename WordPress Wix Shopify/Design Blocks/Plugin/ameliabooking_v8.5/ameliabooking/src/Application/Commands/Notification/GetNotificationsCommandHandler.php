<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\ValueObjects\String\Html;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationsToEntitiesRepository;

/**
 * Class GetNotificationsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class GetNotificationsCommandHandler extends CommandHandler
{
    /**
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetNotificationsCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanRead(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to read notifications');
        }

        $result = new CommandResult();

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');
        /** @var NotificationsToEntitiesRepository $notificationEntitiesRepo */
        $notificationEntitiesRepo = $this->container->get('domain.notificationEntities.repository');
        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->container->get('application.whatsAppNotification.service');

        $whatsAppTemplates = [];
        if ($whatsAppNotificationService->checkRequiredFields()) {
            $whatsAppTemplates = $whatsAppNotificationService->getTemplates();
        }

        /** @var Collection $notifications */
        $notifications = $notificationRepo->getAll();
        /** @var Notification $notification */
        foreach ($notifications->getItems() as $notification) {
            if ($notification->getCustomName()) {
                $notification->setEntityIds($notificationEntitiesRepo->getEntities($notification->getId()->getValue()));
                $notification->setEntityIds(array_map('intval', $notification->getEntityIds()));
            }
            if (!empty($whatsAppTemplates[0]) && !empty($notification->getWhatsAppTemplate()) && !empty($whatsAppTemplates[0])) {
                if (!in_array($notification->getWhatsAppTemplate(), array_column($whatsAppTemplates[0], 'name'))) {
                    $notification->setWhatsAppTemplate('');
                    $notification->setSubject(new Name(''));
                    $notification->setContent(new Html(''));
                    $notificationRepo->updateFieldById($notification->getId()->getValue(), null, 'whatsAppTemplate');
                    $notificationRepo->updateFieldById($notification->getId()->getValue(), '', 'subject');
                    $notificationRepo->updateFieldById($notification->getId()->getValue(), '', 'content');
                }
            }
        }

        if (!$notifications instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get notifications');

            return $result;
        }

        $notificationsArray = $notifications->toArray();

        $notificationsArray = apply_filters('amelia_get_notifications_filter', $notificationsArray);

        do_action('amelia_get_notifications', $notificationsArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved notifications.');
        $result->setData(
            [
            Entities::NOTIFICATIONS => $notificationsArray,
            'whatsAppTemplates'     => !empty($whatsAppTemplates[1]) ? $whatsAppTemplates[1] : []
            ]
        );

        return $result;
    }
}
