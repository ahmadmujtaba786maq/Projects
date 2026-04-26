<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Services\Notification;

/**
 * Interface MailServiceInterface
 *
 * @package AmeliaBooking\Domain\Services\Notification
 */
interface MailServiceInterface
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param      $to
     * @param      $subject
     * @param      $body
     * @param bool $bcc
     *
     * @return mixed
     * @SuppressWarnings(PHPMD)
     */
    public function send($to, $subject, $body, $bcc = false);
}
