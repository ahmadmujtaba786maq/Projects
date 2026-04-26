<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Payment;

use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\Name;

/**
 * Class PaymentGateway
 *
 * @package AmeliaBooking\Domain\Entity\Gateway
 */
class PaymentGateway
{
    /** @var Id */
    private $id;

    /** @var  Name */
    private $name;

    /**
     * PaymentGateway constructor.
     *
     * @param Name $name
     */
    public function __construct(Name $name)
    {
        $this->setName($name);
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
    public function setName(Name $name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            $this->getName()->getValue(),
        ];
    }
}
