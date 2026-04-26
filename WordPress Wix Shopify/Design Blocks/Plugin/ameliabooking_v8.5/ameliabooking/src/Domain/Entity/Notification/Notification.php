<?php

namespace AmeliaBooking\Domain\Entity\Notification;

use AmeliaBooking\Domain\Collection\Collection;
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
 * Class Notification
 *
 * @package AmeliaBooking\Domain\Entity\Notification
 */
class Notification
{
    /** @var Id */
    private $id;

    /** @var Name */
    private $name;

    /** @var string */
    private $customName;

    /** @var NotificationStatus */
    private $status;

    /** @var TimeOfDay */
    private $time;

    /** @var Duration */
    private $timeBefore;

    /** @var Duration */
    private $timeAfter;

    /** @var NotificationType */
    private $type;

    /** @var NotificationSendTo */
    private $sendTo;

    /** @var Name */
    private $subject;

    /** @var Html */
    private $content;

    /** @var BookingType */
    private $entity;

    /** @var  Json */
    private $translations;

    /** @var  array */
    private $entityIds;

    /** @var BooleanValueObject */
    private $sendOnlyMe;

    /** @var Json */
    private $minimumTimeBeforeBooking;

    /** @var string */
    private $whatsAppTemplate;

    /**
     * Notification constructor.
     *
     * @param Name               $name
     * @param NotificationStatus $status
     * @param NotificationType   $type
     * @param BookingType        $entity
     * @param NotificationSendTo $sendTo
     * @param Name               $subject
     * @param Html               $content
     */
    public function __construct(
        Name $name,
        NotificationStatus $status,
        NotificationType $type,
        BookingType $entity,
        NotificationSendTo $sendTo,
        Name $subject,
        Html $content
    ) {
        $this->name    = $name;
        $this->status  = $status;
        $this->type    = $type;
        $this->entity  = $entity;
        $this->sendTo  = $sendTo;
        $this->subject = $subject;
        $this->content = $content;
    }

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
     * @return Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Name $name
     */
    public function setName(Name $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCustomName()
    {
        return $this->customName;
    }

    /**
     * @param string $customName
     */
    public function setCustomName($customName)
    {
        $this->customName = $customName;
    }

    /**
     * @return NotificationStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param NotificationStatus $status
     */
    public function setStatus(NotificationStatus $status)
    {
        $this->status = $status;
    }

    /**
     * @return NotificationType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param NotificationType $type
     */
    public function setType(NotificationType $type)
    {
        $this->type = $type;
    }

    /**
     * @return BookingType
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param BookingType $entity
     */
    public function setEntity(BookingType $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return TimeOfDay
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param TimeOfDay $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return Duration
     */
    public function getTimeBefore()
    {
        return $this->timeBefore;
    }

    /**
     * @param Duration $timeBefore
     */
    public function setTimeBefore($timeBefore)
    {
        $this->timeBefore = $timeBefore;
    }

    /**
     * @return Duration
     */
    public function getTimeAfter()
    {
        return $this->timeAfter;
    }

    /**
     * @param Duration $timeAfter
     */
    public function setTimeAfter($timeAfter)
    {
        $this->timeAfter = $timeAfter;
    }

    /**
     * @return NotificationSendTo
     */
    public function getSendTo()
    {
        return $this->sendTo;
    }

    /**
     * @param NotificationSendTo $sendTo
     */
    public function setSendTo(NotificationSendTo $sendTo)
    {
        $this->sendTo = $sendTo;
    }

    /**
     * @return Name
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param Name $subject
     */
    public function setSubject(Name $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return Html
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param Html $content
     */
    public function setContent(Html $content)
    {
        $this->content = $content;
    }

    /**
     * @return Json
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param Json $translations
     */
    public function setTranslations(Json $translations)
    {
        $this->translations = $translations;
    }

    /**
     * @return array
     */
    public function getEntityIds()
    {
        return $this->entityIds;
    }

    /**
     * @param array $entityIds
     */
    public function setEntityIds($entityIds)
    {
        $this->entityIds = $entityIds;
    }

    /**
     * @return BooleanValueObject
     */
    public function getSendOnlyMe()
    {
        return $this->sendOnlyMe;
    }

    /**
     * @param BooleanValueObject $sendOnlyMe
     */
    public function setSendOnlyMe($sendOnlyMe)
    {
        $this->sendOnlyMe = $sendOnlyMe;
    }

    /**
     * @return string
     */
    public function getWhatsAppTemplate()
    {
        return $this->whatsAppTemplate;
    }

    /**
     * @param string $whatsAppTemplate
     */
    public function setWhatsAppTemplate($whatsAppTemplate)
    {
        $this->whatsAppTemplate = $whatsAppTemplate;
    }

    /**
     * @return Json
     */
    public function getMinimumTimeBeforeBooking()
    {
        return $this->minimumTimeBeforeBooking;
    }

    /**
     * @param Json $minimumTimeBeforeBooking
     */
    public function setMinimumTimeBeforeBooking($minimumTimeBeforeBooking)
    {
        $this->minimumTimeBeforeBooking = $minimumTimeBeforeBooking;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'           => null !== $this->getId() ? $this->getId()->getValue() : null,
            'name'         => $this->getName()->getValue(),
            'customName'   => $this->getCustomName(),
            'status'       => $this->getStatus()->getValue(),
            'type'         => $this->getType()->getValue(),
            'entity'       => $this->getEntity()->getValue(),
            'time'         => null !== $this->getTime() ? $this->getTime()->getValue() : null,
            'timeBefore'   => null !== $this->getTimeBefore() ? $this->getTimeBefore()->getValue() : null,
            'timeAfter'    => null !== $this->getTimeAfter() ? $this->getTimeAfter()->getValue() : null,
            'sendTo'       => $this->getSendTo()->getValue(),
            'subject'      => $this->getSubject()->getValue(),
            'content'      => $this->getContent()->getValue(),
            'translations' => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
            'entityIds'    => $this->getEntityIds(),
            'sendOnlyMe'   => $this->getSendOnlyMe() ? $this->getSendOnlyMe()->getValue() : null,
            'whatsAppTemplate' => $this->getWhatsAppTemplate() ?: null,
            'minimumTimeBeforeBooking' => $this->getMinimumTimeBeforeBooking() ? $this->getMinimumTimeBeforeBooking()->getValue() : null
        ];
    }
}
