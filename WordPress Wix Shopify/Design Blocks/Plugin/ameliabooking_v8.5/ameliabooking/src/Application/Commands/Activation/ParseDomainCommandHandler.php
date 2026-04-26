<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Activation;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\InstallActions\AutoUpdateHook;

/**
 * Class ParseDomainCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Activation
 */
class ParseDomainCommandHandler extends CommandHandler
{
    /**
     * @param ParseDomainCommand $command
     *
     * @return CommandResult
     */
    public function handle(ParseDomainCommand $command)
    {
        $result = new CommandResult();

        $fields = $command->getFields();

        $domain    = AutoUpdateHook::getDomain($fields['domain']);
        $subdomain = AutoUpdateHook::getSubDomain($fields['subdomain']);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully parsed domain');
        $result->setData(
            [
            'domain'    => $domain,
            'subdomain' => $subdomain
            ]
        );

        return $result;
    }
}
