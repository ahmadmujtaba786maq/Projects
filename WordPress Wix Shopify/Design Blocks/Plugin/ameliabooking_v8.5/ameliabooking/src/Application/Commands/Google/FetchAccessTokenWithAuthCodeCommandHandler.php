<?php

namespace AmeliaBooking\Application\Commands\Google;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Factory\Google\GoogleCalendarFactory;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Google\AbstractGoogleCalendarService;
use AmeliaBooking\Infrastructure\Repository\Google\GoogleCalendarRepository;

/**
 * Class FetchAccessTokenWithAuthCodeCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Google
 */
class FetchAccessTokenWithAuthCodeCommandHandler extends CommandHandler
{
    /** @var array */
    public $mandatoryFields = [
        'authCode',
        'userId'
    ];

    /**
     * @param FetchAccessTokenWithAuthCodeCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(FetchAccessTokenWithAuthCodeCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var GoogleCalendarRepository $googleCalendarRepository */
        $googleCalendarRepository = $this->container->get('domain.google.calendar.repository');

        /** @var AbstractGoogleCalendarService $googleCalService */
        $googleCalService = $this->container->get('infrastructure.google.calendar.service');

        $providerId = $command->getField('userId');

        $accessToken = null;
        try {
            $accessToken = $googleCalService->fetchAccessTokenWithAuthCode(
                $command->getField('authCode'),
                $command->getField('isBackend')
                    ? AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-employees'
                    : $command->getField('redirectUri')
            );
        } catch (\Exception $e) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');
            $providerRepository->updateErrorColumn($providerId, $e->getMessage());
        }

        $accessToken = apply_filters('amelia_before_google_calendar_added_filter', $accessToken, $command->getField('userId'));

        $googleCalendar = GoogleCalendarFactory::create(['token' => $accessToken]);

        $googleCalendarRepository->beginTransaction();

        do_action('amelia_before_google_calendar_added', $googleCalendar ? $googleCalendar->toArray() : null, $command->getField('userId'));

        if (!$googleCalendarRepository->add($googleCalendar, $providerId)) {
            $googleCalendarRepository->rollback();
        }

        $googleCalendarRepository->commit();

        do_action('amelia_after_google_calendar_added', $googleCalendar ? $googleCalendar->toArray() : null, $command->getField('userId'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully fetched access token');

        return $result;
    }
}
