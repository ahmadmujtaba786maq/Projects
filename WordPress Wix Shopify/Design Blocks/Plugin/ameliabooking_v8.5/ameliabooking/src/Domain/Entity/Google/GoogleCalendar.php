<?php

namespace AmeliaBooking\Domain\Entity\Google;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class GoogleCalendar
 *
 * @package AmeliaBooking\Domain\Entity\Google
 */
class GoogleCalendar
{
    /** @var Id */
    private $id;

    /** @var Token */
    private $token;

    /** @var Name */
    private $calendarId;

    /**
     * GoogleCalendar constructor.
     *
     * @param Token $token
     * @param Name $calendarId
     */
    public function __construct(
        Token $token,
        Name $calendarId
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
     * @return Name
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * @param Name $calendarId
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
