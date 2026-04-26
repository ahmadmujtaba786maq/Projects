<?php

/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\ValueObjects\String;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;

/**
 * Class PaymentData
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class PaymentData
{
    public const MAX_LENGTH = 4095;
    /**
     * @var string
     */
    private $paymentData;

    /**
     * PaymentData constructor.
     *
     * @param string $paymentData
     *
     * @throws InvalidArgumentException
     */
    public function __construct($paymentData)
    {
        if ($paymentData && strlen($paymentData) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(
                "Payment data \"{$paymentData}\" must be less than " . static::MAX_LENGTH . ' chars'
            );
        }

        $this->paymentData = $paymentData;
    }

    /**
     * Return the payment data from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->paymentData;
    }
}
