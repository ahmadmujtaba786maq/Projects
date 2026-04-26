<?php

namespace AmeliaBooking\Domain\ValueObjects\String;

/**
 * Class PaymentType
 *
 * @package AmeliaBooking\Domain\ValueObjects\String
 */
final class CustomFieldType
{
    public const TEXT     = 'text';
    public const TEXTAREA = 'text-area';
    public const SELECT   = 'select';
    public const CHECKBOX = 'checkbox';
    public const RADIO    = 'radio';
    public const CONTENT  = 'content';
    public const ADDRESS  = 'address';

    /**
     * @var string
     */
    private $type;

    /**
     * Status constructor.
     *
     * @param int $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Return the status from the value object
     *
     * @return string
     */
    public function getValue()
    {
        return $this->type;
    }
}
