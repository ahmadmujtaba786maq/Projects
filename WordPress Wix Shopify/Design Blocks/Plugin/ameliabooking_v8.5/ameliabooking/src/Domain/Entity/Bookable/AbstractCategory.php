<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class AbstractCategory
 *
 * @package AmeliaBooking\Domain\Entity\Bookable
 */
abstract class AbstractCategory
{
    /** @var  Id */
    private $id;

    /** @var  Status */
    protected $status;

    /** @var  Name */
    protected $name;

    /** @var Collection */
    private $serviceList;

    /** @var PositiveInteger */
    protected $position;

    /** @var Json */
    protected $translations;

    /** @var  Color */
    protected $color;

    /** @var  Picture */
    protected $picture;

    /**
     * AbstractCategory constructor.
     *
     * @param Status          $status
     * @param Name            $name
     * @param PositiveInteger $position
     * @param Color           $color
     */
    public function __construct(
        Status $status,
        Name $name,
        PositiveInteger $position,
        Color $color
    ) {
        $this->status   = $status;
        $this->name     = $name;
        $this->position = $position;
        $this->color    = $color;
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
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
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
     * @return Collection
     */
    public function getServiceList()
    {
        return $this->serviceList;
    }

    /**
     * @param Collection $serviceList
     */
    public function setServiceList(Collection $serviceList)
    {
        $this->serviceList = $serviceList;
    }

    /**
     * @return PositiveInteger
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param PositiveInteger $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
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
     * @return Color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param Color $color
     */
    public function setColor(Color $color)
    {
        $this->color = $color;
    }

    /**
     * @return Picture
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * @param Picture $picture
     */
    public function setPicture(Picture $picture)
    {
        $this->picture = $picture;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'               => null !== $this->getId() ? $this->getId()->getValue() : null,
            'status'           => $this->getStatus()->getValue(),
            'name'             => $this->getName()->getValue(),
            'serviceList'      => $this->getServiceList() ? $this->getServiceList()->toArray() : [],
            'position'         => $this->getPosition()->getValue(),
            'translations'     => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
            'color'            => null !== $this->getColor() ? $this->getColor()->getValue() : null,
            'pictureFullPath'  => null !== $this->getPicture() ? $this->getPicture()->getFullPath() : null,
            'pictureThumbPath' => null !== $this->getPicture() ? $this->getPicture()->getThumbPath() : null,
        ];
    }
}
