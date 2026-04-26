<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Helper\HelperService;
use AmeliaBooking\Application\Services\Placeholder\PlaceholderService;
use AmeliaBooking\Application\Services\Notification\EmailNotificationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\NotificationSendTo;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Services\Notification\MailgunService;
use AmeliaBooking\Infrastructure\Services\Notification\OutlookService;
use AmeliaBooking\Infrastructure\Services\Notification\PHPMailService;
use AmeliaBooking\Infrastructure\Services\Notification\SMTPService;
use Exception;
use Interop\Container\Exception\ContainerException;
use Slim\Exception\ContainerValueNotFoundException;

/**
 * Class SendTestEmailCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class SendTestEmailCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'notificationTemplate',
        'recipientEmail'
    ];

    /**
     * @param SendTestEmailCommand $command
     *
     * @return CommandResult
     * @throws ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws ContainerException
     * @throws Exception
     */
    public function handle(SendTestEmailCommand $command)
    {
        if (!$command->getPermissionService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to send test email');
        }

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type');

        /** @var PHPMailService|SMTPService|MailgunService|OutlookService $mailService */
        $mailService = $this->getContainer()->get('infrastructure.mail.service');
        /** @var EmailNotificationService $notificationService */
        $notificationService = $this->getContainer()->get('application.emailNotification.service');
        /** @var PlaceholderService $placeholderService */
        $placeholderService = $this->getContainer()->get("application.placeholder.{$type}.service");
        /** @var SettingsService $settingsAS*/
        $settingsAS = $this->container->get('application.settings.service');
        /** @var \AmeliaBooking\Domain\Services\Settings\SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        /** @var HelperService $helperService */
        $helperService = $this->container->get('application.helper.service');

        $notificationSettings = $settingsService->getCategorySettings('notifications');
        $appointmentsSettings = $settingsService->getCategorySettings('appointments');

        if (!$notificationSettings['senderEmail'] || !$notificationSettings['senderName']) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Test email not sent');

            return $result;
        }

        $notification = $notificationService->getById($command->getField('notificationTemplate'));

        $dummyData = $placeholderService->getPlaceholdersDummyData('email');

        $isForCustomer         = $notification->getSendTo()->getValue() === NotificationSendTo::CUSTOMER;
        $placeholderStringRec  = 'recurring' . 'Placeholders' . ($isForCustomer ? 'Customer' : '');
        $placeholderStringPack = 'package' . 'Placeholders' . ($isForCustomer ? 'Customer' : '');

        $dummyData['recurring_appointments_details'] = $placeholderService->applyPlaceholders($appointmentsSettings[$placeholderStringRec], $dummyData);
        $dummyData['package_appointments_details']   =  $placeholderService->applyPlaceholders($appointmentsSettings[$placeholderStringPack], $dummyData);


        $language = $command->getField('language');
        $info     = json_encode(['locale' => $language]);
        $notificationSubject = $helperService->getBookingTranslation(
            $helperService->getLocaleFromBooking($info),
            $notification->getTranslations() ? $notification->getTranslations()->getValue() : null,
            'subject'
        ) ?: $notification->getSubject()->getValue();

        $notificationContent = $helperService->getBookingTranslation(
            $helperService->getLocaleFromBooking($info),
            $notification->getTranslations() ? $notification->getTranslations()->getValue() : null,
            'content'
        ) ?: $notification->getContent()->getValue();

        $subject = $placeholderService->applyPlaceholders(
            $notificationSubject,
            $dummyData
        );

        $content = $placeholderService->applyPlaceholders(
            $notificationContent,
            $dummyData
        );

        $emailData = apply_filters(
            'amelia_manipulate_test_email_data',
            [
                'email'   => $command->getField('recipientEmail'),
                'subject' => $subject,
                'body'    => $notificationService->getParsedBody($content),
                'bcc'     => $settingsAS->getBccEmails()
            ]
        );

        do_action('amelia_before_send_test_email', $emailData);

        if (empty($emailData['skipSending'])) {
            $mailService->send(
                $emailData['email'],
                $emailData['subject'],
                $emailData['body'],
                $emailData['bcc']
            );
        }

        do_action('amelia_after_send_test_email', $emailData);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Test email successfully sent');

        return $result;
    }
}
