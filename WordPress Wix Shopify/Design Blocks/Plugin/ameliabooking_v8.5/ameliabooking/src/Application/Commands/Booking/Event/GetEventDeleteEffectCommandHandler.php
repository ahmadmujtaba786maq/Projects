<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class GetEventDeleteEffectCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventDeleteEffectCommandHandler extends CommandHandler
{
    /**
     * @param GetEventDeleteEffectCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetEventDeleteEffectCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::EVENTS)) {
            throw new AccessDeniedException('You are not allowed to write events');
        }

        $result = new CommandResult();

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var Event $event */
        $event = $eventRepository->getById((int)$command->getArg('id'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved message.');
        $result->setData(
            [
            'valid'   => $event->getStatus()->getValue() === BookingStatus::REJECTED,
            'message' => $event->getStatus()->getValue() === BookingStatus::REJECTED ? '' : BackendStrings::getEventStrings()['event_cancel_before_delete']
            ]
        );

        return $result;
    }
}
