<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Password
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Password
{
    public const MIN_LENGTH = 4;
    public const MAX_LENGTH = 128;
    /**
     * @var string
     */
    private $password;

    /**
     * Email constructor.
     *
     * @param string $password
     *
     * @throws InvalidArgumentException
     */
    public function __construct($password)
    {
        if ($password && strlen($password) < static::MIN_LENGTH) {
            throw new InvalidArgumentException('Password must be longer than ' . static::MIN_LENGTH . ' char');
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Create a Password value object from an hashed password
     *
     * @param string $password
     *
     * @return Password
     */
    public static function createFromHashedPassword($password)
    {
        $self           = unserialize(sprintf('O:%u:"%s":0:{}', strlen(self::class), self::class));
        $self->password = $password;

        return $self;
    }

    /**
     * Return true if password is valid, otherwise false
     *
     * @param string $password
     *
     * @return bool
     */
    public function checkValidity($password)
    {
        return password_verify($password, $this->password ? $this->password : '');
    }

    /**
     * Return the password from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->password;
    }
}
