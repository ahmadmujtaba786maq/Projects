<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Booking\EventApplicationService;
use AmeliaBooking\Application\Services\Entity\EntityApplicationService;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Application\Services\Zoom\AbstractZoomApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class UpdateEventCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class UpdateEventCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'id',
        'name',
        'periods',
        'applyGlobally'
    ];

    /**
     * @param UpdateEventCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(UpdateEventCommand $command)
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
                [
                    'reauthorize' => true
                ]
            );

            return $result;
        }

        if (
            $userAS->isCustomer($user) ||
            (
                $userAS->isProvider($user) && !$settingsDS->getSetting('roles', 'allowWriteEvents')
            )
        ) {
            throw new AccessDeniedException('You are not allowed to update an event');
        }

        $entityService->removeMissingEntitiesForEvent($eventData);


        /** @var Event $oldEvent */
        $oldEvent = $eventApplicationService->getEventById(
            $eventData['id'],
            [
                'fetchEventsPeriods'    => true,
                'fetchEventsTickets'    => true,
                'fetchEventsTags'       => true,
                'fetchEventsProviders'  => true,
                'fetchEventsImages'     => true,
                'fetchBookings'         => true,
                'fetchBookingsTickets'  => true,
                'fetchBookingsUsers'    => true,
                'fetchBookingsPayments' => true,
            ]
        );

        $eventData =
            apply_filters('amelia_before_event_updated_filter', $eventData, $oldEvent ? $oldEvent->toArray() : null, $command->getField('applyGlobally'));

        do_action('amelia_before_event_updated', $eventData, $oldEvent ? $oldEvent->toArray() : null, $command->getField('applyGlobally'));

        try {
            /** @var Event $event */
            $event = $eventApplicationService->build($eventData);
        } catch (Exception $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($e->getMessage());
            $result->setData(['message' => $e->getMessage()]);
            return $result;
        }

        /** @var DateTimeValue $newUntil */
        $newUntil = $event->getRecurring()
            ? $event->getRecurring()->getUntil()->getValue()->setTime(0, 0, 0)
            : null;

        /** @var DateTimeValue $oldUntil */
        $oldUntil = $oldEvent->getRecurring()
            ? $oldEvent->getRecurring()->getUntil()->getValue()->setTime(0, 0, 0)
            : null;

        if (
            $oldEvent->getRecurring() &&
            $event->getRecurring() &&
            (
                $newUntil < $oldUntil ||
                $event->getRecurring()->getCycle()->getValue() !== $oldEvent->getRecurring()->getCycle()->getValue()
            )
        ) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update event');

            return $result;
        }

        $event->setBookings($oldEvent->getBookings());

        /** @var EventPeriod $oldEventPeriod */
        foreach ($oldEvent->getPeriods()->getItems() as $oldEventPeriod) {
            /** @var EventPeriod $eventPeriod */
            foreach ($event->getPeriods()->getItems() as $eventPeriod) {
                if (
                    $eventPeriod->getId() &&
                    $oldEventPeriod->getId()->getValue() === $eventPeriod->getId()->getValue()
                ) {
                    if ($oldEventPeriod->getZoomMeeting()) {
                        $eventPeriod->setZoomMeeting($oldEventPeriod->getZoomMeeting());
                    }
                    if ($oldEventPeriod->getLessonSpace()) {
                        $eventPeriod->setLessonSpace($oldEventPeriod->getLessonSpace());
                    }
                }
            }
        }

        $eventRepository->beginTransaction();

        try {
            $parsedEvents = $eventApplicationService->update(
                $oldEvent,
                $event,
                $command->getField('applyGlobally')
            );
        } catch (QueryExecutionException $e) {
            $eventRepository->rollback();
            throw $e;
        }

        $eventRepository->commit();

        do_action(
            'amelia_after_event_updated',
            $event ? $event->toArray() : null,
            $oldEvent ? $oldEvent->toArray() : null,
            $command->getField('applyGlobally')
        );

        $providersRemoved = array_udiff(
            $oldEvent->getProviders()->getItems(),
            $event->getProviders()->getItems(),
            function ($a, $b) {
                if ($a->getId()->getValue() == $b->getId()->getValue()) {
                    return 0;
                } else {
                    return ($a->getId()->getValue() < $b->getId()->getValue() ? -1 : 1);
                }
            }
        );

        $providersAdded = array_udiff(
            $event->getProviders()->getItems(),
            $oldEvent->getProviders()->getItems(),
            function ($a, $b) {
                if ($a->getId()->getValue() == $b->getId()->getValue()) {
                    return 0;
                } else {
                    return ($a->getId()->getValue() < $b->getId()->getValue() ? -1 : 1);
                }
            }
        );

        $newInfo = ($event->getDescription() ? $event->getDescription()->getValue() : null) !==
            ($oldEvent->getDescription() ? $oldEvent->getDescription()->getValue() : null) ||
            $event->getName()->getValue() !== $oldEvent->getName()->getValue();

        $zoomUserChanged = ($event->getZoomUserId() ? $event->getZoomUserId()->getValue() : null) !==
            ($oldEvent->getZoomUserId() ? $oldEvent->getZoomUserId()->getValue() : null);

        $zoomUsersLicenced = false;

        if ($oldEvent->getZoomUserId() && $event->getZoomUserId() && $zoomUserChanged) {
            /** @var AbstractZoomApplicationService $zoomService */
            $zoomService = $this->container->get('application.zoom.service');

            $zoomUserType    = 0;
            $zoomOldUserType = 0;
            $zoomResult      = $zoomService->getUsers();
            if (
                !(isset($zoomResult['code']) && $zoomResult['code'] === 124) &&
                !($zoomResult['users'] === null && isset($zoomResult['message']))
            ) {
                $zoomUsers = $zoomResult['users'];
                foreach ($zoomUsers as $key => $val) {
                    if ($val['id'] === $event->getZoomUserId()->getValue()) {
                        $zoomUserType = $val['type'];
                    }
                    if ($val['id'] === $oldEvent->getZoomUserId()->getValue()) {
                        $zoomOldUserType = $val['type'];
                    }
                }
            }
            if ($zoomOldUserType > 1 && $zoomUserType > 1) {
                $zoomUsersLicenced = true;
            }
        }

        $organizerChanged = ($event->getOrganizerId() ? $event->getOrganizerId()->getValue() : null)
            !== ($oldEvent->getOrganizerId() ? $oldEvent->getOrganizerId()->getValue() : null);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated event.');
        $result->setData(
            [
                Entities::EVENTS => $parsedEvents,
                'zoomUserChanged'  => $zoomUserChanged && $event->getZoomUserId() ? $event->getZoomUserId()->getValue() : null,
                'zoomUsersLicenced'  => $zoomUsersLicenced,
                'newInfo'        => $newInfo ? [
                    'name'        => $event->getName(),
                    'description' => $event->getDescription()
                ] : null,
                'newProviders'  => $providersAdded,
                'removeProviders' => $providersRemoved,
                'organizerChanged' => $organizerChanged,
                'newOrganizer'    =>  $event->getOrganizerId() ? $event->getOrganizerId()->getValue() : null,
                'notifyParticipants' => $command->getField('notifyParticipants')
            ]
        );

        return $result;
    }
}
