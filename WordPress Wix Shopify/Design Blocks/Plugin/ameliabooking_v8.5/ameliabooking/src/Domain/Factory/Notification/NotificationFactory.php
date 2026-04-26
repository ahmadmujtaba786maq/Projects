<?php

namespace AmeliaBooking\Domain\Factory\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\TimeOfDay;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Domain\ValueObjects\String\Html;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\NotificationSendTo;
use AmeliaBooking\Domain\ValueObjects\String\NotificationStatus;
use AmeliaBooking\Domain\ValueObjects\String\NotificationType;

/**
 * Class NotificationFactory
 *
 * @package AmeliaBooking\Domain\Factory\Notification
 */
class NotificationFactory
{
    /**
     * @param array $data
     *
     * @return Notification
     * @throws InvalidArgumentException
     */
    public static function create($data)
    {
        $notification = new Notification(
            new Name($data['name']),
            new NotificationStatus($data['status']),
            new NotificationType($data['type']),
            new BookingType($data['entity']),
            new NotificationSendTo($data['sendTo']),
            new Name($data['subject']),
            new Html($data['content'])
        );

        if (isset($data['id'])) {
            $notification->setId(new Id($data['id']));
        }

        if (isset($data['customName']) && !empty($data['customName'])) {
            $notification->setCustomName($data['customName']);
        }

        if (isset($data['time'])) {
            $notification->setTime(new TimeOfDay($data['time']));
        }

        if (isset($data['timeBefore'])) {
            $notification->setTimeBefore(new Duration($data['timeBefore']));
        }

        if (isset($data['translations'])) {
            $notification->setTranslations(new Json($data['translations']));
        }

        if (isset($data['timeAfter'])) {
            $notification->setTimeAfter(new Duration($data['timeAfter']));
        }

        if (isset($data['entityIds'])) {
            $notification->setEntityIds(($data['entityIds']));
        }

        if (isset($data['sendOnlyMe']) && !empty($data['sendOnlyMe'])) {
            $notification->setSendOnlyMe(new BooleanValueObject($data['sendOnlyMe']));
        }

        if (isset($data['whatsAppTemplate'])) {
            $notification->setWhatsAppTemplate($data['whatsAppTemplate']);
        }

        if (isset($data['minimumTimeBeforeBooking'])) {
            $notification->setMinimumTimeBeforeBooking(new Json($data['minimumTimeBeforeBooking']));
        }

        return $notification;
    }
}
