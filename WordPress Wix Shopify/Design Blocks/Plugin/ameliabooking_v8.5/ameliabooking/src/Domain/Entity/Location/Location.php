<?php

namespace AmeliaBooking\Domain\Entity\Location;

use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Address;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\GeoTag;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Phone;
use AmeliaBooking\Domain\ValueObjects\String\Url;

/**
 * Class Location
 *
 * @package AmeliaBooking\Domain\Entity\Location
 */
class Location
{
    /** @var Id */
    private $id;

    /** @var Status */
    private $status;

    /** @var Name */
    private $name;

    /** @var Description */
    private $description;

    /** @var Address */
    private $address;

    /** @var Phone */
    private $phone;

    /** @var GeoTag */
    private $coordinates;

    /** @var Picture */
    private $picture;

    /** @var Url */
    private $pin;

    /** @var  Json */
    protected $translations;

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
     * @return Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Description $description
     */
    public function setDescription(Description $description)
    {
        $this->description = $description;
    }

    /**
     * @return Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    /**
     * @return Phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param Phone $phone
     */
    public function setPhone(Phone $phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return GeoTag
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * @param GeoTag $coordinates
     */
    public function setCoordinates(GeoTag $coordinates)
    {
        $this->coordinates = $coordinates;
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
     * @return Url
     */
    public function getPin()
    {
        return $this->pin;
    }

    /**
     * @param Url $pin
     */
    public function setPin(Url $pin)
    {
        $this->pin = $pin;
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
    public function toArray()
    {
        return [
            'id'               => null !== $this->getId() ? $this->getId()->getValue() : null,
            'status'           => null !== $this->getStatus() ? $this->getStatus()->getValue() : null,
            'name'             => $this->getName() ? $this->getName()->getValue() : '',
            'description'      => null !== $this->getDescription() ? $this->getDescription()->getValue() : null,
            'address'          => $this->getAddress() ? $this->getAddress()->getValue() : null,
            'phone'            => $this->getPhone() ? $this->getPhone()->getValue() : null,
            'latitude'         => $this->getCoordinates() ? $this->getCoordinates()->getLatitude() : null,
            'longitude'        => $this->getCoordinates() ? $this->getCoordinates()->getLongitude() : null,
            'pictureFullPath'  => null !== $this->getPicture() ? $this->getPicture()->getFullPath() : null,
            'pictureThumbPath' => null !== $this->getPicture() ? $this->getPicture()->getThumbPath() : null,
            'pin'              => null !== $this->getPin() ? $this->getPin()->getValue() : null,
            'translations'     => $this->getTranslations() ? $this->getTranslations()->getValue() : null,
        ];
    }
}
