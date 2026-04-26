<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class StripeAccountId
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class StripeAccountId
{
    public const MAX_LENGTH = 255;

    /**
     * @var string
     */
    private $stripeAccountId;

    /**
     * StripeAccountId constructor.
     *
     * @param string $stripeAccountId
     * @throws InvalidArgumentException
     */
    public function __construct($stripeAccountId)
    {
        if (strlen($stripeAccountId) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "StripeAccountId \"{$stripeAccountId}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        $this->stripeAccountId = $stripeAccountId;
    }

    /**
     * Return the stripeAccountId from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->stripeAccountId;
    }
}
