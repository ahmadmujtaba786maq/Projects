<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Services\Notification;

use AmeliaBooking\Domain\Services\Notification\AbstractMailService;
use AmeliaBooking\Domain\Services\Notification\MailServiceInterface;
use AmeliaPHPMailer\PHPMailer\Exception;
use AmeliaPHPMailer\PHPMailer\PHPMailer;

/**
 * Class PHPMailService
 */
class PHPMailService extends AbstractMailService implements MailServiceInterface
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param       $to
     * @param       $subject
     * @param       $body
     * @param array $bccEmails
     * @param array $attachments
     *
     * @return mixed|void
     * @throws Exception
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $bccEmails = [], $attachments = [])
    {
        $mail = new PHPMailer(true);

        try {
            //Recipients
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to);
            $mail->addReplyTo(!empty($this->replyTo) ? $this->replyTo : $this->from);
            foreach ($bccEmails as $bccEmail) {
                $mail->addBCC($bccEmail);
            }

            foreach ($attachments as $attachment) {
                if (!empty($attachment['content'])) {
                    $mail->addStringAttachment($attachment['content'], $attachment['name'], 'base64', $attachment['type']);
                }
            }

            //Content
            $mail->CharSet = 'UTF-8';
            $mail->isHTML();
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
