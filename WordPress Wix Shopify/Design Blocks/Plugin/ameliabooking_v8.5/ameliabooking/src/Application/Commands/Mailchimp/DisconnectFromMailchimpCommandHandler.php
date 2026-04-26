<?php

namespace AmeliaBooking\Application\Commands\Mailchimp;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Interop\Container\Exception\ContainerException;

/**
 * Class DisconnectFromMailchimpCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Mailchimp
 */
class DisconnectFromMailchimpCommandHandler extends CommandHandler
{
    /**
     * @param DisconnectFromMailchimpCommand $command
     *
     * @return CommandResult
     * @throws AccessDeniedException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function handle(DisconnectFromMailchimpCommand $command)
    {

        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::SETTINGS)) {
            throw new AccessDeniedException('You are not allowed to write settings.');
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');


        $result = new CommandResult();

        $mailchimpSettings = $settingsService->getCategorySettings('mailchimp');
        $mailchimpSettings['accessToken'] = null;
        $mailchimpSettings['server'] = null;
        $mailchimpSettings['list'] = null;
        $mailchimpSettings['checkedByDefault'] = false;
        $settingsService->setCategorySettings('mailchimp', $mailchimpSettings);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully logged out of Mailchimp');
        $result->setData(['success' => true]);

        return $result;
    }
}
