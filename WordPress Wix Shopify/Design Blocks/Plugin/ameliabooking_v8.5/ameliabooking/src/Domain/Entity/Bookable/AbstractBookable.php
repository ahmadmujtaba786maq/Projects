<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See COPYING.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\DepositType;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class AbstractBookable
 *
 * @package AmeliaBooking\Domain\Entity\Bookable
 */
abstract class AbstractBookable
{
    /** @var Id */
    private $id;

    /** @var  Name */
    protected $name;

    /** @var Description */
    protected $description;

    /** @var  Color */
    protected $color;

    /** @var DepositType */
    private $depositPayment;

    /** @var  Price */
    protected $deposit;

    /** @var  BooleanValueObject */
    protected $depositPerPerson;

    /** @var  Price */
    protected $price;

    /** @var  Picture */
    protected $picture;

    /** @var PositiveInteger */
    protected $position;

    /** @var Collection */
    private $extras;

    /** @var Collection */
    private $coupons;

    /** @var Json */
    private $settings;

    /** @var  BooleanValueObject */
    protected $fullPayment;


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
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;
    }

    /**
     * @return Price
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

    /**
     * @param Price $deposit
     */
    public function setDeposit(Price $deposit)
    {
        $this->deposit = $deposit;
    }

    /**
     * @return DepositType
     */
    public function getDepositPayment()
    {
        return $this->depositPayment;
    }

    /**
     * @param DepositType $depositPayment
     */
    public function setDepositPayment(DepositType $depositPayment)
    {
        $this->depositPayment = $depositPayment;
    }

    /**
     * @return BooleanValueObject
     */
    public function getDepositPerPerson()
    {
        return $this->depositPerPerson;
    }

    /**
     * @param BooleanValueObject $depositPerPerson
     */
    public function setDepositPerPerson(BooleanValueObject $depositPerPerson)
    {
        $this->depositPerPerson = $depositPerPerson;
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
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return Collection
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param Collection $extras
     */
    public function setExtras(Collection $extras)
    {
        $this->extras = $extras;
    }

    /**
     * @return Collection
     */
    public function getCoupons()
    {
        return $this->coupons;
    }

    /**
     * @param Collection $coupons
     */
    public function setCoupons(Collection $coupons)
    {
        $this->coupons = $coupons;
    }

    /**
     * @return Json
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param Json $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return BooleanValueObject
     */
    public function getFullPayment()
    {
        return $this->fullPayment;
    }

    /**
     * @param BooleanValueObject $fullPayment
     */
    public function setFullPayment(BooleanValueObject $fullPayment)
    {
        $this->fullPayment = $fullPayment;
    }

    /**
     * @return BookableType
     */
    abstract public function getType();

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'               => null !== $this->getId() ? $this->getId()->getValue() : null,
            'name'             => null !== $this->getName() ? $this->getName()->getValue() : null,
            'description'      => null !== $this->getDescription() ? $this->getDescription()->getValue() : null,
            'color'            => null !== $this->getColor() ? $this->getColor()->getValue() : null,
            'price'            => $this->getPrice() ? $this->getPrice()->getValue() : null,
            'deposit'          => null !== $this->getDeposit() ? $this->getDeposit()->getValue() : null,
            'depositPayment'   => null !== $this->getDepositPayment() ? $this->getDepositPayment()->getValue() : null,
            'depositPerPerson' => null !== $this->getDepositPerPerson() ?
                $this->getDepositPerPerson()->getValue() : null,
            'pictureFullPath'  => null !== $this->getPicture() ? $this->getPicture()->getFullPath() : null,
            'pictureThumbPath' => null !== $this->getPicture() ? $this->getPicture()->getThumbPath() : null,
            'extras'           => $this->getExtras() ? $this->getExtras()->toArray() : [],
            'coupons'          => $this->getCoupons() ? $this->getCoupons()->toArray() : [],
            'position'         => null !== $this->getPosition() ? $this->getPosition()->getValue() : null,
            'settings'         => null !== $this->getSettings() ? $this->getSettings()->getValue() : null,
            'fullPayment'      => null !== $this->getFullPayment() ? $this->getFullPayment()->getValue() : null,
        ];
    }
}
