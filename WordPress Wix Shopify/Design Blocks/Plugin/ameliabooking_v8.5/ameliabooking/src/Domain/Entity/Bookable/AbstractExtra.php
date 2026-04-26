<?php

namespace AmeliaBooking\Domain\Entity\Bookable;

use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\String\BookableType;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class AbstractExtra
 *
 * @package AmeliaBooking\Domain\Entity\Bookable
 */
abstract class AbstractExtra
{
    /** @var Id */
    private $id;

    /** @var Name */
    protected $name;

    /** @var Description */
    protected $description;

    /** @var Price */
    protected $price;

    /** @var PositiveInteger */
    protected $maxQuantity;

    /** @var PositiveInteger */
    protected $position;

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
     * @return PositiveInteger
     */
    public function getMaxQuantity()
    {
        return $this->maxQuantity;
    }

    /**
     * @param PositiveInteger $maxQuantity
     */
    public function setMaxQuantity(PositiveInteger $maxQuantity)
    {
        $this->maxQuantity = $maxQuantity;
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
     * @return BookableType
     */
    abstract public function getType();

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'          => null !== $this->getId() ? $this->getId()->getValue() : null,
            'name'        => $this->getName() ? $this->getName()->getValue() : null,
            'description' => $this->getDescription() ? $this->getDescription()->getValue() : null,
            'price'       => $this->getPrice() ? $this->getPrice()->getValue() : null,
            'maxQuantity' => $this->getMaxQuantity() ? $this->getMaxQuantity()->getValue() : null,
            'position'    => $this->getPosition() ? $this->getPosition()->getValue() : null,
            'type'        => $this->getType() ? $this->getType()->getValue() : null,
        ];
    }
}
