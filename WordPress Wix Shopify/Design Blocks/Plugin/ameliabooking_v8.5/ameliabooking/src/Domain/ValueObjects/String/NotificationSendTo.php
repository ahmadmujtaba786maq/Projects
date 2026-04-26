<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class NotificationSendTo
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class NotificationSendTo
{
    public const CUSTOMER = 'customer';
    public const PROVIDER = 'provider';

    /**
     * @var string
     */
    private $notificationSendTo;

    /**
     * NotificationSendTo constructor.
     *
     * @param $notificationSendTo
     */
    public function __construct($notificationSendTo)
    {
        $this->notificationSendTo = $notificationSendTo;
    }

    /**
     * Return the notification send to from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->notificationSendTo;
    }
}
