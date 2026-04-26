<?php

namespace AmeliaBooking\Application\Commands\User\Provider;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\ProviderApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class GetProviderCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Provider
 */
class GetProviderCommandHandler extends CommandHandler
{
    /**
     * @param GetProviderCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(GetProviderCommand $command)
    {
        /** @var int $providerId */
        $providerId = (int)$command->getField('id');

        /** @var AbstractUser $currentUser */
        $currentUser = $this->container->get('logged.in.user');

        if (
            !$command->getPermissionService()->currentUserCanRead(Entities::EMPLOYEES) ||
            (
                !$command->getPermissionService()->currentUserCanReadOthers(Entities::EMPLOYEES) &&
                $currentUser->getId()->getValue() !== $providerId
            )
        ) {
            throw new AccessDeniedException('You are not allowed to read employee.');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var ProviderApplicationService $providerService */
        $providerService = $this->container->get('application.user.provider.service');
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        /** @var AbstractGoogleCalendarService $googleCalService */
        $googleCalService = $this->container->get('infrastructure.google.calendar.service');
        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');
        /** @var ProviderRepository $providerRepository */
        $providerRepository = $this->container->get('domain.users.providers.repository');


        $companyDaysOff = $settingsService->getCategorySettings('daysOff');

        $companyDayOff = $providerService->checkIfTodayIsCompanyDayOff($companyDaysOff);

        /** @var Provider $provider */
        $provider = $providerService->getProviderWithServicesAndSchedule($providerId, true);

        $providerService->modifyPeriodsWithSingleLocationAfterFetch($provider->getWeekDayList());
        $providerService->modifyPeriodsWithSingleLocationAfterFetch($provider->getSpecialDayList());

        $futureAppointmentsServicesIds = $appointmentRepository->getFutureAppointmentsServicesIds(
            [$provider->getId()->getValue()],
            DateTimeService::getNowDateTime(),
            null
        );

        $providerArray = $providerService->manageProvidersActivity(
            [$provider->toArray()],
            $companyDayOff
        )[0];

        $successfulGoogleConnection = true;

        $successfulOutlookConnection = true;

        try {
            $providerArray['googleCalendar']['calendarList'] = $googleCalService->listCalendarList($provider);

            $providerArray['googleCalendar']['calendarId'] = $googleCalService->getProviderGoogleCalendarId($provider);
        } catch (\Exception $e) {
            $providerArray['googleCalendar']['calendarId'] = !empty($providerArray['googleCalendar']['calendarId'])
                ? $providerArray['googleCalendar']['calendarId']
                : null;

            $providerArray['googleCalendar']['calendarList'] = [];

            $providerRepository->updateErrorColumn($providerId, $e->getMessage());
            $successfulGoogleConnection = false;
        }

        try {
            $providerArray['outlookCalendar']['calendarList'] = $outlookCalendarService->listCalendarList($provider);

            $providerArray['outlookCalendar']['calendarId'] = $outlookCalendarService->getProviderOutlookCalendarId(
                $provider
            );
        } catch (\Exception $e) {
            $providerArray['outlookCalendar']['calendarId'] = !empty($providerArray['outlookCalendar']['calendarId'])
                ? $providerArray['outlookCalendar']['calendarId']
                : null;

            $providerArray['outlookCalendar']['calendarList'] = [];

            $providerRepository->updateErrorColumn($providerId, $e->getMessage());
            $successfulOutlookConnection = false;
        }

        $providerArray['mandatoryServicesIds'] = $providerService->getMandatoryServicesIds($providerId);

        $providerArray = apply_filters('amelia_get_provider_filter', $providerArray);

        do_action('amelia_get_provider', $providerArray);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved user.');
        $result->setData(
            [
                Entities::USER                  => $providerArray,
                'successfulGoogleConnection'    => $successfulGoogleConnection,
                'successfulOutlookConnection'   => $successfulOutlookConnection,
                'futureAppointmentsServicesIds' => $futureAppointmentsServicesIds,
            ]
        );

        return $result;
    }
}
