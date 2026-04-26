<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class WhatsAppWebhookCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class WhatsAppWebhookRegisterCommandHandler extends CommandHandler
{
    /**
     * @param WhatsAppWebhookRegisterCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(WhatsAppWebhookRegisterCommand $command)
    {
        $result = new CommandResult();

        $params = $command->getField('params');

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        if (
            $params['hub_mode'] == 'subscribe' &&
            $params['hub_verify_token'] == $settingsDS->getSetting('notifications', 'whatsAppReplyToken')
        ) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('WhatsApp Verify token successfully validated');

            $result->setData($params['hub_challenge']);
        } else {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage("Can't validate token");
            $result->setData("Can't validate WhatsApp verify token");
        }

        return $result;
    }
}
