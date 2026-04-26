<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Cache;

use AmeliaBooking\Domain\ValueObjects\Json;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class Cache
 *
 * @package AmeliaBooking\Domain\Entity\Cache
 */
class Cache
{
    /** @var Id */
    private $id;

    /** @var  Name */
    private $name;

    /** @var  Id */
    private $paymentId;

    /** @var  Json */
    protected $data;

    /**
     * Cache constructor.
     *
     * @param Name $name
     */
    public function __construct(
        Name $name
    ) {
        $this->name = $name;
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
     * @return Id
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param Id $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return Json
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Json $data
     */
    public function setData(Json $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'        => $this->getId() ? $this->getId()->getValue() : null,
            'name'      => $this->getName()->getValue(),
            'paymentId' => $this->getPaymentId() ? $this->getPaymentId()->getValue() : null,
            'data'      => $this->getData() ? $this->getData()->getValue() : null,
        ];
    }
}
