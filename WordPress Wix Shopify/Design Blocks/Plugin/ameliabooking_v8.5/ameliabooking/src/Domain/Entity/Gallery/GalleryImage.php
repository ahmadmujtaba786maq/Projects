<?php

namespace AmeliaBooking\Domain\Entity\Gallery;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;

/**
 * Class GalleryImage
 *
 * @package AmeliaBooking\Domain\Entity\Gallery
 */
class GalleryImage
{
    /** @var Id */
    private $id;

    /** @var Id */
    private $entityId;

    /** @var EntityType */
    private $entityType;

    /** @var Picture */
    private $picture;

    /** @var PositiveInteger */
    protected $position;

    /**
     * GalleryImage constructor.
     *
     * @param EntityType      $entityType
     * @param Picture         $picture
     * @param PositiveInteger $position
     */
    public function __construct(
        EntityType $entityType,
        Picture $picture,
        PositiveInteger $position
    ) {
        $this->entityType = $entityType;
        $this->picture    = $picture;
        $this->position   = $position;
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
     * @return Id
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param Id $entityId
     */
    public function setEntityId(Id $entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * @return EntityType
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param EntityType $entityType
     */
    public function setEntityType(EntityType $entityType)
    {
        $this->entityType = $entityType;
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
     * @return PositiveInteger
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param PositiveInteger $position
     */
    public function setPosition(PositiveInteger $position)
    {
        $this->position = $position;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'               => null !== $this->getId() ? $this->getId()->getValue() : null,
            'entityId'         => null !== $this->getEntityId() ? $this->getEntityId()->getValue() : null,
            'entityType'       => null !== $this->getEntityType() ? $this->getEntityType()->getValue() : null,
            'pictureFullPath'  => null !== $this->getPicture() ? $this->getPicture()->getFullPath() : null,
            'pictureThumbPath' => null !== $this->getPicture() ? $this->getPicture()->getThumbPath() : null,
            'position'         => null !== $this->getPosition() ? $this->getPosition()->getValue() : null,
        ];
    }
}
