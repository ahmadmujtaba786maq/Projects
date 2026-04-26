<?php

namespace AmeliaBooking\Domain\Factory\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Notification\NotificationLog;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use Exception;

/**
 * Class NotificationLogFactory
 *
 * @package AmeliaBooking\Domain\Factory\Notification
 */
class NotificationLogFactory
{
    /**
     * @param array $data
     *
     * @return NotificationLog
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function create($data)
    {
        $notificationLog = new NotificationLog();

        if (isset($data['id'])) {
            $notificationLog->setId(new Id($data['id']));
        }

        if (isset($data['notificationId'])) {
            $notificationLog->setNotificationsId(new Id($data['notificationId']));
        }

        if (isset($data['userId'])) {
            $notificationLog->setUserId(new Id($data['userId']));
        }

        if (isset($data['appointmentId'])) {
            $notificationLog->setAppointmentId(new Id($data['appointmentId']));
        }

        if (isset($data['eventId'])) {
            $notificationLog->setEventId(new Id($data['eventId']));
        }

        if (isset($data['packageCustomerId'])) {
            $notificationLog->setPackageCustomerId(new Id($data['packageCustomerId']));
        }

        if (isset($data['sentDateTime'])) {
            $notificationLog->setSentDateTime(
                new DateTimeValue(
                    DateTimeService::getCustomDateTimeObjectFromUtc($data['sentDateTime'])
                )
            );
        }

        if (isset($data['sent'])) {
            $notificationLog->setSent(new BooleanValueObject($data['sent']));
        }

        if (isset($data['data'])) {
            $notificationLog->setData(new Json($data['data']));
        }

        return $notificationLog;
    }
}
