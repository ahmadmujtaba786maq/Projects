<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

/**
 * Class Id
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Integer
 */
final class Id
{
    /**
     * @var int
     */
    private $id;

    /**
     * Id constructor.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = (int)$id;
    }

    /**
     * Return the password from the value object
     *
     * @return int
     */
    public function getValue()
    {
        return $this->id;
    }
}
