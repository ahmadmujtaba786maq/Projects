<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Url
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Url
{
    public const MAX_LENGTH = 65535;

    /**
     * @var string
     */
    private $url;

    /**
     * Description constructor.
     *
     * @param string $url
     *
     * @throws InvalidArgumentException
     */
    public function __construct($url)
    {
        if ($url && strlen($url) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Url \"{$url}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        $this->url = $url;
    }

    /**
     * Return the url from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->url;
    }
}
