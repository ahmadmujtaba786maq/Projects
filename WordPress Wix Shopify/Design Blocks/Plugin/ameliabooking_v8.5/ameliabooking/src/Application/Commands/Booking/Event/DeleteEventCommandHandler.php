<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;

/**
 * Class DeleteEventCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class DeleteEventCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'applyGlobally'
    ];

    /**
     * @param DeleteEventCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeleteEventCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanDelete(Entities::EVENTS)) {
            throw new AccessDeniedException('You are not allowed to delete event');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        $event = $eventRepository->getById($command->getArg('id'));

        $eventRepository->beginTransaction();


        do_action('amelia_before_event_deleted', $event ? $event->toArray() : null);

        try {
            $deletedEvents = $eventApplicationService->delete($event, $command->getField('applyGlobally'));
        } catch (QueryExecutionException $e) {
            $eventRepository->rollback();
            throw $e;
        }

        $eventRepository->commit();

        do_action('amelia_after_event_deleted', $event ? $event->toArray() : null);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted event');
        $result->setData(
            [
            Entities::EVENT => $event->toArray(),
            'deletedEvents' => $deletedEvents
            ]
        );

        return $result;
    }
}
