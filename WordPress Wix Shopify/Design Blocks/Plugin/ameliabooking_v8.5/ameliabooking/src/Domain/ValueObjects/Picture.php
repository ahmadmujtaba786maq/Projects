<?php

namespace AmeliaBooking\Domain\ValueObjects;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class Picture
 *
 * @package AmeliaBooking\Domain\ValueObjects
 */
final class Picture
{
    public const MAX_LENGTH = 767;

    /**
     * @var string
     */
    private $pathToFull;

    /**
     * @var string
     */
    private $pathToThumb;

    /**
     * Name constructor.
     *
     * @param string $pathToFull
     * @param string $pathToThumb
     *
     * @throws InvalidArgumentException
     */
    public function __construct($pathToFull, $pathToThumb)
    {
        if (empty($pathToFull)) {
            throw new InvalidArgumentException("Path to full can't be empty");
        }

        if (strlen($pathToFull) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Path to full \"{$pathToFull}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        if (empty($pathToThumb)) {
            throw new InvalidArgumentException("Path to thumb can't be empty");
        }

        if (strlen($pathToThumb) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Path to thumb string length \"{$pathToThumb}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        $this->pathToFull  = $pathToFull;
        $this->pathToThumb = $pathToThumb;
    }

    /**
     * Return the Full path
     *
     * @return string
     */
    public function getFullPath()
    {
        return $this->pathToFull;
    }

    /**
     * Return the Thumb path
     *
     * @return string
     */
    public function getThumbPath()
    {
        return $this->pathToThumb;
    }
}
