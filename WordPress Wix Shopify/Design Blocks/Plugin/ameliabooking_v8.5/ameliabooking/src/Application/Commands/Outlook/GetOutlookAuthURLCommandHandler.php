<?php

namespace AmeliaBooking\Application\Commands\Outlook;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\ProviderRepository;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetOutlookAuthURLCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Outlook
 */
class GetOutlookAuthURLCommandHandler extends CommandHandler
{
    /**
     * @param GetOutlookAuthURLCommand $command
     *
     * @return CommandResult
     * @throws ContainerException
     * @throws QueryExecutionException
     */
    public function handle(GetOutlookAuthURLCommand $command)
    {
        $result = new CommandResult();

        /** @var AbstractOutlookCalendarService $outlookCalendarService */
        $outlookCalendarService = $this->container->get('infrastructure.outlook.calendar.service');

        $providerId = (int)$command->getField('id');

        $authUrl = null;
        try {
            $authUrl = $outlookCalendarService->createAuthUrl($providerId);
        } catch (\Exception $e) {
            /** @var ProviderRepository $providerRepository */
            $providerRepository = $this->container->get('domain.users.providers.repository');
            $providerRepository->updateErrorColumn($providerId, $e->getMessage());

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Error retrieved outlook authorization URL');
            $result->setData(
                [
                    'authUrl' => ''
                ]
            );

            return $result;
        }


        $authUrl = apply_filters('amelia_get_outlook_calendar_auth_url_filter', $authUrl, $command->getField('id'));

        do_action('amelia_get_outlook_calendar_auth_url', $authUrl, $command->getField('id'));

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved outlook authorization URL');
        $result->setData(
            [
                'authUrl' => filter_var($authUrl, FILTER_SANITIZE_URL)
            ]
        );

        return $result;
    }
}
