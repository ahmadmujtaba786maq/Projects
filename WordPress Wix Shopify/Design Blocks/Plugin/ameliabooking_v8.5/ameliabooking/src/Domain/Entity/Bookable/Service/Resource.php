<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\PositiveInteger;
use AmeliaBooking\Domain\ValueObjects\String\EntityType;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\ValueObjects\String\Status;

/**
 * Class Resource
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Resource
{
    /** @var Id */
    private $id;

    /** @var Name */
    private $name;

    /** @var PositiveInteger */
    private $quantity;

    /** @var EntityType */
    private $shared;

    /** @var Status */
    private $status;

    /** @var array */
    private $entities;

    /** @var BooleanValueObject */
    private $countAdditionalPeople;


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
     * @return Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Name $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return PositiveInteger
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param PositiveInteger $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return EntityType
     */
    public function getShared()
    {
        return $this->shared;
    }

    /**
     * @param EntityType $shared
     */
    public function setShared($shared)
    {
        $this->shared = $shared;
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
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @param array $entities
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;
    }

    /**
     * @return BooleanValueObject
     */
    public function getCountAdditionalPeople()
    {
        return $this->countAdditionalPeople;
    }

    /**
     * @param BooleanValueObject $countAdditionalPeople
     */
    public function setCountAdditionalPeople($countAdditionalPeople)
    {
        $this->countAdditionalPeople = $countAdditionalPeople;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'         => !empty($this->getId()) ? $this->getId()->getValue() : null,
            'name'       => !empty($this->getName()) ? $this->getName()->getValue() : '',
            'quantity'   => !empty($this->getQuantity()) ? $this->getQuantity()->getValue() : 1,
            'shared'     => !empty($this->getShared()) ? $this->getShared()->getValue() : false,
            'status'     => $this->getStatus() ? $this->getStatus()->getValue() : Status::VISIBLE,
            'entities'   => $this->getEntities(),
            'countAdditionalPeople' => $this->getCountAdditionalPeople() ? $this->getCountAdditionalPeople()->getValue() : null
        ];
    }
}
