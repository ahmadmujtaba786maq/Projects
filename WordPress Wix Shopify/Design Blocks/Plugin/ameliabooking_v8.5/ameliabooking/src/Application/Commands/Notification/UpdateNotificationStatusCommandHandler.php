<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;

/**
 * Class UpdateNotificationStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class UpdateNotificationStatusCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'status'
    ];

    /**
     * @param UpdateNotificationStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateNotificationStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to update notification');
        }

        $notificationId = (int)$command->getArg('id');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        $status = $command->getField('status');

        do_action('amelia_before_notification_status_updated', $status, $notificationId);

        if ($notificationRepo->updateFieldById($notificationId, $status, 'status')) {
            do_action('amelia_after_notification_status_updated', $status, $notificationId);

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated notification.');
            $result->setData(true);
        }

        return $result;
    }
}
