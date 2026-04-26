<?php

namespace AmeliaBooking\Application\Commands\Mailchimp;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService;

/**
 * Class FetchAccessTokenMailchimpCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Mailchimp
 */
class FetchAccessTokenMailchimpCommandHandler extends CommandHandler
{
    /**
     * @param FetchAccessTokenMailchimpCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws AccessDeniedException
     */
    public function handle(FetchAccessTokenMailchimpCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AbstractMailchimpService $mailchimpService */
        $mailchimpService = $this->container->get('infrastructure.mailchimp.service');

        /** @var \AmeliaBooking\Domain\Services\Settings\SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::SETTINGS)) {
            throw new AccessDeniedException('You are not allowed to write settings.');
        }

        $accessToken = $command->getFields()['access_token'];

        if (empty($accessToken)) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Failed to fetch access token');
            $result->setData(
                [
                    'error' => $command->getFields()['error'],
                ]
            );

            $result->setUrl(AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-settings&mailchimp=1&mailchimp_error=1');

            return $result;
        }

        $server      = $mailchimpService->getMetadataServerName($accessToken);

        $mailchimpSettings = $settingsService->getCategorySettings('mailchimp');
        $mailchimpSettings['accessToken'] = $accessToken;
        $mailchimpSettings['server'] = $server;
        $settingsService->setCategorySettings('mailchimp', $mailchimpSettings);

        $lists = $mailchimpService->getLists();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully fetched access token');
        $result->setData(
            [
                'lists' => $lists
            ]
        );

        $result->setUrl(AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-settings&mailchimp=1');

        return $result;
    }
}
