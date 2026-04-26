<?php

namespace AmeliaBooking\Domain\Entity\Zoom;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Url;

/**
 * Class ZoomMeeting
 *
 * @package AmeliaBooking\Domain\Entity\Zoom
 */
class ZoomMeeting
{
    /** @var Id */
    private $id;

    /** @var Url */
    private $joinUrl;

    /** @var Url */
    private $startUrl;

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
     * @return Url
     */
    public function getJoinUrl()
    {
        return $this->joinUrl;
    }

    /**
     * @param Url $joinUrl
     */
    public function setJoinUrl(Url $joinUrl)
    {
        $this->joinUrl = $joinUrl;
    }

    /**
     * @return Url
     */
    public function getStartUrl()
    {
        return $this->startUrl;
    }

    /**
     * @param Url $startUrl
     */
    public function setStartUrl(Url $startUrl)
    {
        $this->startUrl = $startUrl;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'       => $this->getId() ? $this->getId()->getValue() : null,
            'startUrl' => $this->getStartUrl() ? $this->getStartUrl()->getValue() : null,
            'joinUrl'  => $this->getJoinUrl() ? $this->getJoinUrl()->getValue() : null,
        ];
    }
}
