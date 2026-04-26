<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class CustomFieldSaveType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class CustomFieldSaveType
{
    public const BOOKINGS = 'bookings';
    public const CUSTOMER = 'customer';

    /**
     * @var string
     */
    private $saveType;

    /**
     * Status constructor.
     *
     * @param int $saveType
     */
    public function __construct($saveType)
    {
        $this->saveType = $saveType;
    }

    /**
     * Return the status from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->saveType;
    }
}
