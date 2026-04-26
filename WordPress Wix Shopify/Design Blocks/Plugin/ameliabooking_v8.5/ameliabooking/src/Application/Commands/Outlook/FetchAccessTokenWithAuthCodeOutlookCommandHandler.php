<?php

namespace AmeliaBooking\Application\Commands\Outlook;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Outlook\OutlookCalendar;
use AmeliaBooking\Domain\Factory\Outlook\OutlookCalendarFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use AmeliaBooking\Infrastructure\Repository\Outlook\OutlookCalendarRepository;

/**
 * Class FetchAccessTokenWithAuthCodeOutlookCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Outlook
 */
class FetchAccessTokenWithAuthCodeOutlookCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'authCode',
        'userId'
    ];

    /**
     * @param FetchAccessTokenWithAuthCodeOutlookCommand $command
     *
     * @return CommandResult
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function handle(FetchAccessTokenWithAuthCodeOutlookCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var OutlookCalendarRepository $outlookCalendarRepository */
        $outlookCalendarRepository = $this->container->get('domain.outlook.calendar.repository');

        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');

        $providerId = $command->getField('userId');

        $token = null;
        try {
            $token = $outlookCalendarService->fetchAccessTokenWithAuthCode(
                $command->getField('authCode'),
                $command->getField('redirectUri'),
                $providerId
            );
        } catch (\Exception $e) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');

            $providerRepository->updateErrorColumn($providerId, $e->getMessage());

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData([]);
            $result->setMessage($e->getMessage());

            return $result;
        }

        if (!$token || !$token['outcome']) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setData($token);
            $result->setMessage($token['result']);

            return $result;
        }

        if (!$providerId) {
            /** @var SettingsService $settingsService */
            $settingsService = $this->getContainer()->get('domain.settings.service');

            $settings = $settingsService->getAllSettingsCategorized();

            $settings['outlookCalendar']['token'] = json_decode($token['result'], true);

            $settingsService->setAllSettings($settings);

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully fetched access token');

            return $result;
        }

        $token = apply_filters('amelia_before_outlook_calendar_added_filter', $token, $command->getField('userId'));

        /** @var OutlookCalendar $outlookCalendar */
        $outlookCalendar = OutlookCalendarFactory::create(['token' => $token['result']]);

        $outlookCalendarRepository->beginTransaction();

        do_action('amelia_before_outlook_calendar_added', $outlookCalendar ? $outlookCalendar->toArray() : null, $command->getField('userId'));

        if (!$outlookCalendarRepository->add($outlookCalendar, $providerId)) {
            $outlookCalendarRepository->rollback();
        }

        $outlookCalendarRepository->commit();

        do_action('amelia_after_outlook_calendar_added', $outlookCalendar ? $outlookCalendar->toArray() : null, $command->getField('userId'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully fetched access token');

        return $result;
    }
}
