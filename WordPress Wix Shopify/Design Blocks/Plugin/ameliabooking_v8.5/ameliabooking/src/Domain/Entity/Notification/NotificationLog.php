<?php

namespace AmeliaBooking\Domain\Entity\Notification;

use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;

/**
 * Class NotificationLog
 *
 * @package AmeliaBooking\Domain\Entity\Notification
 */
class NotificationLog
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $notificationsId;

    /** @var Id */
    private $userId;

    /** @var Id */
    private $appointmentId;

    /** @var Id */
    private $eventId;

    /** @var Id */
    private $packageCustomerId;

    /** @var DateTimeValue */
    private $sentDateTime;

    /** @var BooleanValueObject */
    private $sent;

    /** @var Json */
    private $data;

    /**
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Id $id
     */
    public function setId(Id $id)
    {
        $this->id = $id;
    }

    /**
     * @return Id
     */
    public function getNotificationsId()
    {
        return $this->notificationsId;
    }

    /**
     * @param Id $notificationsId
     */
    public function setNotificationsId($notificationsId)
    {
        $this->notificationsId = $notificationsId;
    }

    /**
     * @return Id
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param Id $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return Id
     */
    public function getAppointmentId()
    {
        return $this->appointmentId;
    }

    /**
     * @param Id $appointmentId
     */
    public function setAppointmentId($appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }

    /**
     * @return Id
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param Id $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @return Id
     */
    public function getPackageCustomerId()
    {
        return $this->packageCustomerId;
    }

    /**
     * @param Id $packageCustomerId
     */
    public function setPackageCustomerId($packageCustomerId)
    {
        $this->packageCustomerId = $packageCustomerId;
    }

    /**
     * @return DateTimeValue
     */
    public function getSentDateTime()
    {
        return $this->sentDateTime;
    }

    /**
     * @param DateTimeValue $sentDateTime
     */
    public function setSentDateTime($sentDateTime)
    {
        $this->sentDateTime = $sentDateTime;
    }

    /**
     * @return BooleanValueObject
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @param BooleanValueObject $sent
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
    }

    /**
     * @return Json
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Json $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                => $this->getId() ? $this->getId()->getValue() : null,
            'notificationId'    => $this->getNotificationsId()->getValue(),
            'userId'            => $this->getUserId()->getValue(),
            'appointmentId'     => $this->getAppointmentId() ? $this->getAppointmentId()->getValue() : null,
            'eventId'           => $this->getEventId() ? $this->getEventId()->getValue() : null,
            'packageCustomerId' => $this->getPackageCustomerId() ? $this->getPackageCustomerId()->getValue() : null,
            'sentDateTime'      => $this->getSentDateTime()->getValue(),
            'sent'              => $this->getSent() ? $this->getSent()->getValue() : null,
            'data'              => $this->getData() ? $this->getData()->getValue() : null,
        ];
    }
}
