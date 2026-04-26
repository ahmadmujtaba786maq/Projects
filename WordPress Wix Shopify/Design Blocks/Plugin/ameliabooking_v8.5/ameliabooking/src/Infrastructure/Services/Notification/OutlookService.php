<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use AmeliaBooking\Infrastructure\Services\Outlook\AbstractOutlookCalendarService;
use Exception;

/**
 * Class OutlookService
 */
class OutlookService extends AbstractMailService implements MailServiceInterface
{
    /** @var AbstractOutlookCalendarService */
    private $outlookCalendarService;

    /**
     * OutlookService constructor.
     *
     * @param AbstractOutlookCalendarService $outlookCalendarService
     * @param string $from
     * @param string $fromName
     * @param string $replyTo
     */
    public function __construct($outlookCalendarService, $from, $fromName, $replyTo)
    {
        $this->outlookCalendarService = $outlookCalendarService;

        parent::__construct($from, $fromName, $replyTo);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array  $bcc
     * @param array  $attachments
     *
     * @return void
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $bcc = [], $attachments = [])
    {
        $attachmentsList = [];

        foreach ($attachments as $attachment) {
            if (!empty($attachment['content'])) {
                $isInvoice = strpos($attachment['type'], 'pdf') !== false;

                if ($isInvoice) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'Invoice_');
                } else {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'cal_');
                }

                if (
                    $tmpFile &&
                    file_put_contents($tmpFile, $attachment['content']) !== false &&
                    @rename($tmpFile, $tmpFile .= ($isInvoice ? '.pdf' : '.ics')) !== false
                ) {
                    $attachmentsList[] = ['filePath' => $tmpFile, 'fileName' => $attachment['name']];
                }
            }
        }

        $this->outlookCalendarService->sendEmail(
            $this->from,
            $this->fromName,
            $this->replyTo,
            $to,
            $subject,
            $body,
            $bcc,
            $attachmentsList
        );
    }
}
