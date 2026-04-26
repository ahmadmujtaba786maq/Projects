<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class EntityType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class EntityType
{
    public const SERVICE = Entities::SERVICE;

    /**
     * @var string
     */
    private $type;

    /**
     * Status constructor.
     *
     * @param int $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Return the type from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->type;
    }
}
