<?php

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;

/**
 * Class MailerFactory
 *
 * @package AmeliaBooking\Infrastructure\Services\Notification
 */
class MailerFactory
{
    /**
     * Mailer constructor.
     *
     * @param Container $container
     *
     * @return MailgunService|PHPMailService|SMTPService|WpMailService|OutlookService
     */
    public static function create(Container $container)
    {
        $settingsService = $container->get('domain.settings.service');

        $settings = $settingsService->getCategorySettings('notifications');

        $outlookSettings = $settingsService->getCategorySettings('outlookCalendar');

        if ($settings['mailService'] === 'smtp') {
            return new SMTPService(
                $settings['senderEmail'],
                $settings['senderName'],
                $settings['smtpHost'],
                $settings['smtpPort'],
                $settings['smtpSecure'],
                $settings['smtpUsername'],
                $settings['smtpPassword'],
                $settings['replyTo']
            );
        }

        if ($settings['mailService'] === 'mailgun') {
            return new MailgunService(
                $settings['senderEmail'],
                $settings['senderName'],
                $settings['mailgunApiKey'],
                $settings['mailgunDomain'],
                $settings['mailgunEndpoint'],
                $settings['replyTo']
            );
        }

        if ($settings['mailService'] === 'wp_mail') {
            return new WpMailService(
                $settings['senderEmail'],
                $settings['senderName'],
                $settings['replyTo']
            );
        }

        if ($settings['mailService'] === 'outlook' && $outlookSettings['mailEnabled']) {
            /** @var AbstractOutlookCalendarService $outlookCalendarService */
            $outlookCalendarService = $container->get('infrastructure.outlook.calendar.service');

            return new OutlookService(
                $outlookCalendarService,
                $settings['senderEmail'],
                $settings['senderName'],
                $settings['replyTo']
            );
        }

        return new PHPMailService(
            $settings['senderEmail'],
            $settings['senderName'],
            $settings['replyTo']
        );
    }
}
