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
 * Class DeactivatePluginCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Activation
 */
class DeactivatePluginCommandHandler extends CommandHandler
{
    /**
     * @param DeactivatePluginCommand $command
     *
     * @return CommandResult
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeactivatePluginCommand $command)
    {
        $result = new CommandResult();

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $purchaseCode = trim(
            $settingsService->getSetting('activation', 'purchaseCodeStore')
        );

        // Get the base domain from query string
        $domain = $command->getField('params')['domain'];
        $domain = AutoUpdateHook::getDomain($domain);

        // Get the subdomain from query string
        $subdomain = $command->getField('params')['subdomain'];
        $subdomain = AutoUpdateHook::getSubDomain($subdomain);

        // Call the TMS Store API to check if purchase code is valid
        $ch = curl_init(
            AMELIA_STORE_API_URL . 'activation/code/deactivate?slug=ameliabooking&purchaseCode=' .
            $purchaseCode . '&domain=' . $domain . '&subdomain=' . $subdomain
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Response from the TMS Store
        $response = json_decode(curl_exec($ch));

        curl_close($ch);

        // Update Amelia Settings
        if ($response->deactivated === true) {
            $settingsService->setSetting('activation', 'active', false);
            $settingsService->setSetting('activation', 'purchaseCodeStore', '');
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully checked purchase code');
        $result->setData(
            [
            'deactivated' => $response->deactivated,
            ]
        );

        return $result;
    }
}
