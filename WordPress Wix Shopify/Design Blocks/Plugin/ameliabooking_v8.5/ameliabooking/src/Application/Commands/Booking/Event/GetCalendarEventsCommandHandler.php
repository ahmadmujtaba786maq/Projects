<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\Booking\Event\EventPeriodFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\RecurringFactory;
use AmeliaBooking\Domain\Services\Booking\EventDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Recurring;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetCalendarEventsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetCalendarEventsCommandHandler extends CommandHandler
{
    /**
     * @var array
     */
    public $mandatoryFields = [
        'periods'
    ];

    /**
     * @param GetCalendarEventsCommand $command
     *
     * @return CommandResult
     * @throws QueryExecutionException
     * @throws ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(GetCalendarEventsCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');
        /** @var AbstractGoogleCalendarService $googleCalendarService */
        $googleCalendarService = $this->container->get('infrastructure.google.calendar.service');
        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');
        /** @var EventDomainService $eventDomainService */
        $eventDomainService = $this->container->get('domain.booking.event.service');
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');

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
            (
                $userAS->isProvider($user) && !$settingsDS->getSetting('roles', 'allowWriteEvents')
            )
        ) {
            throw new AccessDeniedException('You are not allowed to read an event');
        }

        $events = [];

        $periodList = $command->getField('periods');

        $eventParams = $command->getField('eventIds');

        /** @var Collection $providers */
        $providers = $providerRepository->getWithSchedule(
            [
                'fetchCalendars' => true,
                'providers'      => array_column($command->getField('providers'), 'id'),
            ]
        );

        $eventIds = $eventRepository->getRecurringIds($eventParams[0], $eventParams[1]);

        $eventIds[] = $eventParams[0];

        $recurringData = $command->getField('recurring');

        $periodList = array_map(
            function ($val) {
                return EventPeriodFactory::create($val);
            },
            $periodList
        );

        if ($recurringData['until']) {
            /** @var Recurring $recurring */
            $recurring = RecurringFactory::create($recurringData);

            $recurringEventsPeriods = $eventDomainService->getRecurringEventsPeriods(
                $recurring,
                new Collection($periodList)
            );

            if (!empty($recurringEventsPeriods)) {
                /** @var Collection $recurringPeriod */
                foreach ($recurringEventsPeriods as $recurringPeriod) {
                    if (!empty($recurringPeriod['periods'])) {
                        $periodList = array_merge($periodList, $recurringPeriod['periods']->getItems());
                    }
                }
            }
        }

        /** @var Provider $provider */
        foreach ($providers->getItems() as $provider) {
            $providerArray = $provider->toArray();

            /** @var EventPeriod $period */
            foreach ($periodList as $period) {
                $periodStart    = DateTimeService::getCustomDateTimeRFC3339($period->getPeriodStart()->getValue()->format('Y-m-d H:i:s'));
                $periodEnd      = DateTimeService::getCustomDateTimeRFC3339($period->getPeriodEnd()->getValue()->format('Y-m-d H:i:s'));
                $periodStartEnd = explode('T', $periodStart)[0] . 'T' . explode('T', $periodEnd)[1];

                try {
                    $events = array_merge($events, $googleCalendarService->getEvents($providerArray, $periodStart, $periodStartEnd, $periodEnd, $eventIds));
                } catch (Exception $e) {
                }

                try {
                    $events = array_merge($events, $outlookCalendarService->getEvents($providerArray, $periodStart, $periodStartEnd, $periodEnd, $eventIds));
                } catch (Exception $e) {
                }

                $events = apply_filters('amelia_get_calendar_events_filter', $events, $period->toArray(), $providerArray);

                do_action('amelia_get_calendar_events', $events, $period->toArray(), $providerArray);

                if (count($events) > 0) {
                    $result->setResult(CommandResult::RESULT_CONFLICT);
                    $result->setMessage("Conflict with the event in employee's google/outlook calendar");
                    $result->setData(
                        [
                            'calendarConflict' => true,
                            'events'   => $events
                        ]
                    );

                    return $result;
                }
            }
        }


        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved google events.');
        $result->setData(
            [
                'events' => $events,
            ]
        );

        return $result;
    }
}
