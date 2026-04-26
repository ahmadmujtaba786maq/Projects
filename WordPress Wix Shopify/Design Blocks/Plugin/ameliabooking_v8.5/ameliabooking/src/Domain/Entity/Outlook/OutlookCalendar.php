<?php

namespace AmeliaBooking\Domain\Entity\Outlook;

use AmeliaBooking\Domain\ValueObjects\String\Email;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Label;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class OutlookCalendar
 *
 * @package AmeliaBooking\Domain\Entity\Outlook
 */
class OutlookCalendar
{
    /** @var Id */
    private $id;

    /** @var Token */
    private $token;

    /** @var Label */
    private $calendarId;

    /**
     * OutlookCalendar constructor.
     *
     * @param Token $token
     * @param Label $calendarId
     */
    public function __construct(
        Token $token,
        Label $calendarId
    ) {
        $this->token      = $token;
        $this->calendarId = $calendarId;
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
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param Token $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return Label
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * @param Label $calendarId
     */
    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'         => null !== $this->getId() ? $this->getId()->getValue() : null,
            'token'      => $this->getToken()->getValue(),
            'calendarId' => null !== $this->getCalendarId() ? $this->getCalendarId()->getValue() : null,
        ];
    }
}
