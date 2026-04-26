<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Html
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Html
{
    public const MAX_LENGTH = 65535;

    /**
     * @var string
     */
    private $html;

    /**
     * Description constructor.
     *
     * @param string $html
     *
     * @throws InvalidArgumentException
     */
    public function __construct($html)
    {
        if ($html && strlen($html) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Description \"{$html}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        $this->html = $html;
    }

    /**
     * Return the html from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->html;
    }
}
