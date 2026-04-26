<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class UpdateEventStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class UpdateEventStatusCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'status',
        'applyGlobally'
    ];

    /**
     * @param UpdateEventStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateEventStatusCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWriteStatus(Entities::EVENTS)) {
            throw new AccessDeniedException('You are not allowed to update event status');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        $requestedStatus = $command->getField('status');

        /** @var Event $event */
        $event = $eventRepository->getById((int)$command->getArg('id'));

        $eventRepository->beginTransaction();

        do_action('amelia_before_event_status_updated', $event ? $event->toArray() : null, $requestedStatus, $command->getField('applyGlobally'));

        try {
            /** @var Collection $updatedEvents */
            $updatedEvents = $eventApplicationService->updateStatus(
                $event,
                $requestedStatus,
                $command->getField('applyGlobally')
            );
        } catch (QueryExecutionException $e) {
            $eventRepository->rollback();
            throw $e;
        }

        $eventRepository->commit();

        do_action('amelia_after_event_status_updated', $event ? $event->toArray() : null, $requestedStatus, $command->getField('applyGlobally'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated event status');
        $result->setData(
            [
            'status'         => $requestedStatus,
            'message'        => BackendStrings::getEventStrings()['event_status_changed'] . $requestedStatus,
            Entities::EVENTS => $updatedEvents->toArray(),
            ]
        );

        return $result;
    }
}
