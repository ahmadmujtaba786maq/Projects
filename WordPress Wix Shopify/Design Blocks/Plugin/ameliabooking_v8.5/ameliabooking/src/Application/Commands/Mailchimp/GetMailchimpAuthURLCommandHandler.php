<?php

namespace AmeliaBooking\Application\Commands\Mailchimp;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Infrastructure\Services\Mailchimp\AbstractMailchimpService;
use Interop\Container\Exception\ContainerException;

/**
 * Class GetMailchimpAuthURLCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Mailchimp
 */
class GetMailchimpAuthURLCommandHandler extends CommandHandler
{
    /**
     * @param GetMailchimpAuthURLCommand $command
     *
     * @return CommandResult
     * @throws ContainerException
     */
    public function handle(GetMailchimpAuthURLCommand $command)
    {
        $result = new CommandResult();

        /** @var AbstractMailchimpService $mailchimpService */
        $mailchimpService = $this->container->get('infrastructure.mailchimp.service');

        $authUrl = $mailchimpService->createAuthUrl();

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved mailchimp authorization URL');
        $result->setData(
            [
            'authUrl' => filter_var($authUrl, FILTER_SANITIZE_URL)
            ]
        );

        return $result;
    }
}
