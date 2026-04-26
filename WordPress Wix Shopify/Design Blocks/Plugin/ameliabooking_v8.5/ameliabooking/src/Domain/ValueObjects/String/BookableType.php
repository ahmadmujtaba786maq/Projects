<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Entity\Entities;

/**
 * Class BookableType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class BookableType
{
    public const SERVICE = Entities::SERVICE;

    public const EVENT = Entities::EVENT;
    public const PACKAGE = Entities::PACKAGE;

    /**
     * @var string
     */
    private $bookableType;

    /**
     * BookableType constructor.
     *
     * @param string $bookableType
     */
    public function __construct($bookableType)
    {
        $this->bookableType = $bookableType;
    }

    /**
     * Return the bookable type from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->bookableType;
    }
}
