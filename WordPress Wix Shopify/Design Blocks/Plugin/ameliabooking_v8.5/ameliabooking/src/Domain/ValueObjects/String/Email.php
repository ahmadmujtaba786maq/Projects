<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Email
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Email
{
    public const MAX_LENGTH = 255;
    /**
     * @var string
     */
    private $email;

    /**
     * Email constructor.
     *
     * @param string $email
     *
     * @throws InvalidArgumentException
     */
    public function __construct($email)
    {
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email '$email' is not a valid email");
        }
        if ($email && strlen($email) > static::MAX_LENGTH) {
            throw new InvalidArgumentException("Email '$email' must be less than " . static::MAX_LENGTH . ' chars');
        }
        $this->email = $email;
    }

    /**
     * Return the email from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->email;
    }
}
