<?php

namespace AmeliaBooking\Application\Controller\Notification;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\Notification\UpdateNotificationCommand;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateNotificationController
 *
 * @package AmeliaBooking\Application\Controller\Notification
 */
class UpdateNotificationController extends Controller
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
        'time',
        'entity',
        'timeBefore',
        'timeAfter',
        'subject',
        'content',
        'translations',
        'entityIds',
        'status',
        'type',
        'whatsAppTemplate',
        'minimumTimeBeforeBooking'
    ];

    /**
     * Instantiates the Update Notification command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateNotificationCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command     = new UpdateNotificationCommand($args);
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
        $eventBus->emit('notification.updated', $result);
    }
}
