<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class Cycle
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class Cycle
{
    public const DAILY   = 'daily';
    public const WEEKLY  = 'weekly';
    public const MONTHLY = 'monthly';
    public const YEARLY  = 'yearly';
    /**
     * @var string
     */
    private $cycle;

    /**
     * Cycle constructor.
     *
     * @param string $cycle
     */
    public function __construct($cycle)
    {
        $this->cycle = $cycle;
    }

    /**
     * Return the cycle from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->cycle;
    }
}
