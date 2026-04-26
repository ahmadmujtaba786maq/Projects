<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\Notification\NotificationHelperService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationsToEntitiesRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateNotificationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class UpdateNotificationCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'subject',
        'content'
    ];

    /**
     * @param UpdateNotificationCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(UpdateNotificationCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to update notification');
        }

        $notificationId = (int)$command->getArg('id');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $notificationData = $command->getFields();

        if (empty($notificationData['entityIds'])) {
            $notificationData['entityIds'] = [];
        }

        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        $entityService->removeMissingEntitiesForNotification($notificationData);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');
        /** @var NotificationsToEntitiesRepository $notificationEntitiesRepo */
        $notificationEntitiesRepo = $this->container->get('domain.notificationEntities.repository');
        /** @var EventRepository $eventRepo */
        $eventRepo = $this->container->get('domain.booking.event.repository');
        /** @var NotificationHelperService $notificationHelper */
        $notificationHelper = $this->container->get('application.notificationHelper.service');

        /** @var Notification $currentNotification */
        $currentNotification = $notificationRepo->getById($notificationId);
        $currentEntityList   = $notificationEntitiesRepo->getEntities($notificationId);

        $content = $command->getField('content');

        if ($command->getField('type') === 'email') {
            $content = preg_replace("/\r|\n/", "", $content);
        }

        if ($command->getField('type') !== 'whatsapp') {
            $contentRes    = $notificationHelper->parseAndReplace($content);
            $parsedContent = $contentRes[0];
            $content       = $contentRes[1];
        }

        $isCustom = $command->getField('customName') !== null ;

        $notificationData['id']      = $notificationId;
        $notificationData['name']    = $isCustom ? $command->getField('name') : $currentNotification->getName()->getValue();
        $notificationData['status']  = $command->getField('status') ?: $currentNotification->getStatus()->getValue();
        $notificationData['type']    = $currentNotification->getType()->getValue();
        $notificationData['sendTo']  = $currentNotification->getSendTo()->getValue();
        $notificationData['content'] = $content;

        $notificationData = apply_filters('amelia_before_notification_updated_filter', $notificationData);

        do_action('amelia_before_notification_updated', $notificationData);

        /** @var Notification $notification */
        $notification = NotificationFactory::create($notificationData);

        $minimumTime = $command->getField('minimumTimeBeforeBooking');
        if (!empty($minimumTime) && json_encode($minimumTime)) {
            $notification->setMinimumTimeBeforeBooking(new Json(json_encode($minimumTime)));
        }

        if (!$notification instanceof Notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update notification entity.');

            return $result;
        }

        if ($notificationRepo->update($notificationId, $notification)) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated notification.');
            $result->setData(
                [
                    Entities::NOTIFICATION => $notification->toArray(),
                    'update'               => !empty($parsedContent)
                ]
            );
        }

        if ($notification->getCustomName()) {
            $removeEntities = array_diff($currentEntityList, $notification->getEntityIds());
            $addEntities    = array_diff($notification->getEntityIds(), $currentEntityList);

            foreach ($removeEntities as $removeEntity) {
                $notificationEntitiesRepo->removeEntity($notificationId, $removeEntity, $notification->getEntity()->getValue());
            }
            foreach ($addEntities as $addEntity) {
                $recurringMain = null;
                if ($notification->getEntity()->getValue() === Entities::EVENT) {
                    $recurring = $eventRepo->isRecurring($addEntity);
                    if ($recurring['event_recurringOrder'] !== null) {
                        $recurringMain = $recurring['event_recurringOrder'] === 1 ? $addEntity : $recurring['event_parentId'];
                    }
                }
                $notificationEntitiesRepo->addEntity($notificationId, $recurringMain ?: $addEntity, $notification->getEntity()->getValue());
            }
        }

        do_action('amelia_after_notification_updated', $notification->toArray());

        return $result;
    }
}
