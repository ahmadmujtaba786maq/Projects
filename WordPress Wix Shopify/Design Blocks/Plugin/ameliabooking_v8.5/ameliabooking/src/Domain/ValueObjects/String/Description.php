<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Description
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Description
{
    public const MAX_LENGTH = 65535;
    /**
     * @var string
     */
    private $description;

    /**
     * Description constructor.
     *
     * @param string $description
     *
     * @throws InvalidArgumentException
     */
    public function __construct($description)
    {
        if ($description && strlen($description) > static::MAX_LENGTH) {
            $shortDescription = substr($description, 0, 10) . '...';
            throw new InvalidArgumentException(
                "Description \"{$shortDescription}\" must be less than " . static::MAX_LENGTH . ' characters'
            );
        }

        $this->description = $description;
    }

    /**
     * Return the description from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->description;
    }
}
