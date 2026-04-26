<?php

namespace AmeliaBooking\Domain\ValueObjects\Number\Integer;

/**
 * Class LoginType
 *
 * @package AmeliaBooking\Domain\ValueObjects\Number\Integer
 */
final class LoginType
{
    public const WP_CREDENTIALS      = 1;
    public const WP_USER             = 2;
    public const AMELIA_CREDENTIALS  = 3;
    public const AMELIA_URL_TOKEN    = 4;
    public const AMELIA_SOCIAL_LOGIN = 5;

    /**
     * @var int
     */
    private $type;

    /**
     * Status constructor.
     *
     * @param int $type
     */
    public function __construct($type)
    {
        $this->type = (int)$type;
    }

    /**
     * Return the type from the value object
     *
     * @return int
     */
    public function getValue()
    {
        return $this->type;
    }
}
