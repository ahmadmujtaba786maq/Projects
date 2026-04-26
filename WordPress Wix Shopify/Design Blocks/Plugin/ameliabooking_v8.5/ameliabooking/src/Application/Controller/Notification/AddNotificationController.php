<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\Command;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Notification\AddNotificationCommand;
use AmeliaBooking\Application\Commands\Notification\UpdateNotificationCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddNotificationController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class AddNotificationController extends Controller
{
    /**
     * Fields for notification that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'name',
        'customName',
        'sendOnlyMe',
        'type',
        'time',
        'timeBefore',
        'timeAfter',
        'subject',
        'content',
        'translations',
        'sendTo',
        'status',
        'notificationType',
        'appointmentStatus',
        'when',
        'entityIds',
        'duplicate',
        'entity',
        'whatsAppTemplate',
        'minimumTimeBeforeBooking'
    ];

    /**
     * Instantiates the Add Notification command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddNotificationCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new AddNotificationCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('notification.added', $result);
    }
}
