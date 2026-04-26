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
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationsToEntitiesRepository;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddNotificationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class AddNotificationCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'name',
        'type',
        'subject',
        'content'
    ];

    /**
     * @param AddNotificationCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     */
    public function handle(AddNotificationCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to create notifications');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $notificationData = $command->getFields();

        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        if ($missingEntity = $entityService->getMissingEntityForNotification($notificationData)) {
            return $entityService->getMissingEntityResponse($missingEntity);
        }

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');
        /** @var NotificationsToEntitiesRepository $notificationEntitiesRepo */
        $notificationEntitiesRepo = $this->container->get('domain.notificationEntities.repository');
        /** @var EventRepository $eventRepo */
        $eventRepo = $this->container->get('domain.booking.event.repository');
        /** @var NotificationHelperService $notificationHelper */
        $notificationHelper = $this->container->get('application.notificationHelper.service');


        $content = $command->getField('content');

        if ($command->getField('type') === 'email') {
            $content = preg_replace("/\r|\n/", "", $content);
        }

        if ($command->getField('type') !== 'whatsapp') {
            $contentRes    = $notificationHelper->parseAndReplace($content);
            $parsedContent = $contentRes[0];
            $content       = $contentRes[1];
        }

        $notificationArray = $command->getFields();

        $notificationArray['content'] = $content;

        $notificationArray = apply_filters('amelia_before_notification_added_filter', $notificationArray);

        do_action('amelia_before_notification_added', $notificationArray);

        $notification = NotificationFactory::create($notificationArray);

        $minimumTime = $command->getField('minimumTimeBeforeBooking');
        if (!empty($minimumTime) && json_encode($minimumTime)) {
            $notification->setMinimumTimeBeforeBooking(new Json(json_encode($minimumTime)));
        }

        if (!$notification instanceof Notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not add notification entity.');

            return $result;
        }

        $id = $notificationRepo->add($notification);
        if ($id) {
            $notification->setId(new Id($id));
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully added notification.');
            $result->setData(
                [
                Entities::NOTIFICATION => $notification->toArray(),
                'update'               => !empty($parsedContent),
                'id'                   => $id
                ]
            );
        }

        $addEntities = $notification->getEntityIds();
        foreach ($addEntities as $addEntity) {
            $recurringMain = null;
            if ($notification->getEntity()->getValue() === Entities::EVENT) {
                $recurring = $eventRepo->isRecurring($addEntity);
                if ($recurring['event_recurringOrder'] !== null) {
                    $recurringMain = $recurring['event_recurringOrder'] === 1 ? $addEntity : $recurring['event_parentId'];
                }
            }
            $notificationEntitiesRepo->addEntity($id, $recurringMain ?: $addEntity, $notification->getEntity()->getValue());
        }

        do_action('amelia_after_notification_added', $notification->toArray());

        return $result;
    }
}
