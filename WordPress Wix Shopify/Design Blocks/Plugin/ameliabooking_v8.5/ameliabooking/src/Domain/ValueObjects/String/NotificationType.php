<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class NotificationType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class NotificationType
{
    public const EMAIL    = 'email';
    public const SMS      = 'sms';
    public const WHATSAPP = 'whatsapp';

    /**
     * @var string
     */
    private $notificationType;

    /**
     * NotificationType constructor.
     *
     * @param string $notificationType
     */
    public function __construct($notificationType)
    {
        $this->notificationType = $notificationType;
    }

    /**
     * Return the notification type from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->notificationType;
    }
}
