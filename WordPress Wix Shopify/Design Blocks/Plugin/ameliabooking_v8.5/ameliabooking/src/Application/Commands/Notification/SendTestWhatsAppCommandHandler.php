<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Notification\AbstractWhatsAppNotificationService;
use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\String\NotificationSendTo;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class SendTestWhatsAppCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class SendTestWhatsAppCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'notificationTemplate',
        'recipientWhatsApp'
    ];

    /**
     * @param SendTestWhatsAppCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(SendTestWhatsAppCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to send test whatsapp messages');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type');

        /** @var AbstractWhatsAppNotificationService $whatsAppNotificationService */
        $whatsAppNotificationService = $this->container->get('application.whatsAppNotification.service');
        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->getContainer()->get("application.placeholder.{$type}.service");
        /** @var \AmeliaBooking\Domain\Services\Settings\SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $appointmentsSettings = $settingsService->getCategorySettings('appointments');

        $notification = $whatsAppNotificationService->getById($command->getField('notificationTemplate'));

        if (!$whatsAppNotificationService->checkRequiredFields() || !$notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('WhatsApp not enabled or required fields not filled');

            return $result;
        }

        $dummyData = $placeholderService->getPlaceholdersDummyData('whatsapp');

        $isForCustomer = $notification->getSendTo()->getValue() === NotificationSendTo::CUSTOMER;

        $placeholderStringRec  = 'recurring' . 'Placeholders' . ($isForCustomer ? 'Customer' : '') . 'Sms';
        $placeholderStringPack = 'package' . 'Placeholders' . ($isForCustomer ? 'Customer' : '') . 'Sms';

        $recPh  = str_replace(array("\n","\r"), '; ', $appointmentsSettings[$placeholderStringRec]);
        $packPh = str_replace(array("\n","\r"), '; ', $appointmentsSettings[$placeholderStringPack]);

        $recPh  = preg_replace('!\s+!', ' ', $recPh);
        $packPh = preg_replace('!\s+!', ' ', $packPh);

        $dummyData['recurring_appointments_details'] = $placeholderService->applyPlaceholders($recPh, $dummyData);
        $dummyData['package_appointments_details']   =  $placeholderService->applyPlaceholders($packPh, $dummyData);

        $dummyData = apply_filters('amelia_before_send_test_whatsapp_filter', $dummyData, $notification->toArray(), $command->getField('recipientWhatsApp'));

        do_action('amelia_before_send_test_whatsapp', $dummyData, $notification->toArray(), $command->getField('recipientWhatsApp'));

        $response = $whatsAppNotificationService->sendTestNotification(
            $command->getField('recipientWhatsApp'),
            $notification,
            $dummyData
        );

        do_action('amelia_after_send_test_whatsapp', $dummyData, $notification->toArray(), $command->getField('recipientWhatsApp'));

        if (!empty($response['error'])) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage($response['error']);

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Test whatsapp successfully sent');

        return $result;
    }
}
