<?php

namespace AmeliaBooking\Application\Commands\Apple;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\AuthorizationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Apple\AbstractAppleCalendarService;

class GetAppleCalendarListCommandHandler extends CommandHandler
{
    /**
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     */
    public function handle(GetAppleCalendarListCommand $command)
    {
        /** @var UserApplicationService $userAS */
        $userAS = $this->getContainer()->get('application.user.service');

        if (!$command->getPermissionService()->currentUserCanRead(Entities::EMPLOYEES)) {
            try {
                /** @var AbstractUser $user */
                $user = $userAS->authorization(
                    $command->getToken(),
                    Entities::PROVIDER
                );
            } catch (AuthorizationException $e) {
                $result = new CommandResult();
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setData(
                    [
                        'reauthorize' => true
                    ]
                );

                return $result;
            }

            if ($userAS->isCustomer($user)) {
                throw new AccessDeniedException('You are not allowed');
            }
        }

        $result = new CommandResult();

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $appleCalendarSettings = $settingsDS->getCategorySettings('appleCalendar');
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');
        /** @var Provider $provider */
        $provider = $providerRepository->getById($command->getArg('id'));

        $employeeAppleCalendar = $provider && $provider->getEmployeeAppleCalendar() ? $provider->getEmployeeAppleCalendar()->toArray() : null;

        $appleCalendarId = $provider && $provider->getAppleCalendarId() ? $provider->getAppleCalendarId()->getValue() : null;

        /** @var AbstractAppleCalendarService $appleCalendarService */
        $appleCalendarService = $this->container->get('infrastructure.apple.calendar.service');

        $appleId       =
            $employeeAppleCalendar && $employeeAppleCalendar['iCloudId'] ?
            $employeeAppleCalendar['iCloudId'] :
            $appleCalendarSettings['clientID'];
        $applePassword =
            $employeeAppleCalendar && $employeeAppleCalendar['appSpecificPassword'] ?
                $employeeAppleCalendar['appSpecificPassword'] :
                $appleCalendarSettings['clientSecret'];

        $credentials  = $appleCalendarService->handleAppleCredentials($appleId, $applePassword);
        $calendarList = [];
        if ($credentials) {
            $calendarsUrl = $appleCalendarService->getCalendarsUrl($appleId, $applePassword);

            if ($calendarsUrl) {
                $calendars = $appleCalendarService->getCalendars($appleId, $applePassword);
                if ($calendars) {
                    if ($appleCalendarId) {
                        $calendars = $this->filterCalendars($calendars, $appleCalendarId, $provider, $providerRepository);
                    }
                    foreach ($calendars as $calendar) {
                        if ($calendar['name'] !== '' && $calendar['id'] !== '' && $calendar['privilege'] === 'write') {
                            $calendarList[] = $calendar;
                        }
                    }
                }
            }
            if (!$appleCalendarId && $provider && $employeeAppleCalendar) {
                $providerRepository->updateFieldById($provider->getId()->getValue(), $calendarList[0]['id'], 'appleCalendarId');
            }
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully retrieved calendar list.');
            $result->setData(
                [
                    'calendarList' => $calendarList,
                    'appleCalendarId' => ($provider && $provider->getAppleCalendarId())
                        ? $provider->getAppleCalendarId()->getValue()
                        : (($employeeAppleCalendar === null)
                            ? null
                            : $calendarList[0]['id']),
                    'isEmployeeConnectedToPersonalAppleCalendar' => (bool)$employeeAppleCalendar,
                ]
            );

            return $result;
        } else {
            $result->setDataInResponse(true);
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Make sure you are using the correct iCloud email address and app-specific password.');
            return $result;
        }
    }

    /**
     * @throws QueryExecutionException
     */
    private function filterCalendars(
        $calendars,
        $appleCalendarId,
        Provider $provider,
        ProviderRepository $providerRepository
    ) {
        // Check if appleCalendarId exists in the calendars array
        $exists = false;
        foreach ($calendars as $calendar) {
            if ($calendar['id'] === $appleCalendarId) {
                $exists = true;
                break;
            }
        }

        // If the appleCalendarId does not exist, set it to null
        if (!$exists) {
            $providerRepository->updateFieldById($provider->getId()->getValue(), null, 'appleCalendarId');
        }

        return $calendars;
    }
}
