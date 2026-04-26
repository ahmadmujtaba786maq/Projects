<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\NotificationSendTo;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;
use DOMDocument;
use DOMElement;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class DeleteNotificationCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class DeleteNotificationCommandHandler extends CommandHandler
{
    /**
     * @param DeleteNotificationCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function handle(DeleteNotificationCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to delete notifications');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        /** @var Notification $notification */
        $notification = $notificationRepo->getById($command->getArg('id'));

        if (!$notification instanceof Notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete notification.');

            return $result;
        }

        if ($notification->getCustomName() === null) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Can not delete default notifications.');

            return $result;
        }

        $notificationRepo->beginTransaction();

        do_action('amelia_before_notification_deleted', $notification->toArray());

        if (!$notificationRepo->delete($notification->getId()->getValue())) {
            $notificationRepo->rollback();
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Unable to delete notification.');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted notification.');
        $result->setData(
            [
                Entities::NOTIFICATION => $notification->toArray()
            ]
        );

        $notificationRepo->commit();

        do_action('amelia_after_notification_deleted', $notification->toArray());

        return $result;
    }
}
