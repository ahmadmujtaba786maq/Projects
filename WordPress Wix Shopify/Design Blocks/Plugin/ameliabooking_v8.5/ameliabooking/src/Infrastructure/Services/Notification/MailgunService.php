<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use Mailgun\Mailgun;

/**
 * Class MailgunService
 */
class MailgunService extends AbstractMailService implements MailServiceInterface
{
    /** @var string */
    private $apiKey;

    /** @var string */
    private $domain;

    /** @var string */
    private $endpoint;

    /**
     * MailgunService constructor.
     *
     * @param string $from
     * @param string $fromName
     * @param string $apiKey
     * @param string $domain
     * @param string $endpoint
     */
    public function __construct($from, $fromName, $apiKey, $domain, $endpoint, $replyTo)
    {
        parent::__construct($from, $fromName, $replyTo);
        $this->apiKey   = $apiKey;
        $this->domain   = $domain;
        $this->endpoint = $endpoint;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param       $to
     * @param       $subject
     * @param       $body
     * @param array $bccEmails
     * @param array $attachments
     *
     * @return mixed|void
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $bccEmails = [], $attachments = [])
    {
        $mgClient = $this->endpoint ? Mailgun::create($this->apiKey, $this->endpoint) : Mailgun::create($this->apiKey);

        $mgArgs = [
            'from'       => "{$this->fromName} <{$this->from}>",
            'to'         => $to,
            'subject'    => $subject,
            'html'       => $body,
            'attachment' => [],
            'h:Reply-To' => !empty($this->replyTo) ? $this->replyTo : $this->from
        ];

        if ($bccEmails) {
            $mgArgs['bcc'] = implode(', ', $bccEmails);
        }

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
                    $mgArgs['attachment'][] = ['filePath' => $tmpFile, 'filename' => $tmpFile];
                }
            }
        }

        $mgClient->messages()->send($this->domain, $mgArgs);
    }
}
