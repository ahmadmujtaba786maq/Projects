<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class AddEventCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class AddEventCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'name',
        'periods'
    ];

    /**
     * @param AddEventCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(AddEventCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $eventData = $command->getFields();

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');
        /** @var EventApplicationService $eventApplicationService */
        $eventApplicationService = $this->container->get('application.booking.event.service');
        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var EntityApplicationService $entityService */
        $entityService = $this->container->get('application.entity.service');

        try {
            /** @var AbstractUser $user */
            $user = $command->getUserApplicationService()->authorization(
                $command->getPage() === 'cabinet' ? $command->getToken() : null,
                $command->getCabinetType()
            );
        } catch (AuthorizationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData(
                ['reauthorize' => true]
            );

            return $result;
        }

        if (
            $userAS->isCustomer($user) ||
            ($userAS->isProvider($user) && !$settingsDS->getSetting('roles', 'allowWriteEvents'))
        ) {
            throw new AccessDeniedException('You are not allowed to add an event');
        }

        $entityService->removeMissingEntitiesForEvent($eventData);

        $eventRepository->beginTransaction();

        $eventData = apply_filters('amelia_before_event_added_filter', $eventData);

        do_action('amelia_before_event_added', $eventData);

        try {
            /** @var Event $event */
            $event = $eventApplicationService->build($eventData);
        } catch (Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(['message' => $e->getMessage()]);
            return $result;
        }


        try {
            /** @var Collection $events */
            $events = $eventApplicationService->add($event);
        } catch (QueryExecutionException $e) {
            $eventRepository->rollback();
            throw $e;
        }

        $eventRepository->commit();

        do_action('amelia_after_event_added', $event ? $event->toArray() : null);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added new event.');
        $result->setData(
            [
                Entities::EVENTS => $events->toArray(),
            ]
        );

        return $result;
    }
}
