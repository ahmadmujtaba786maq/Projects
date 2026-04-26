<?php

namespace AmeliaBooking\Infrastructure\DB\MySQLi;

/**
 * Class Query
 *
 * @package AmeliaBooking\Infrastructure\DB\MySQLi
 */
class Query
{
    private $value;

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
