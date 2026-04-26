<?php

namespace AmeliaBooking\Infrastructure\DB\MySQLi;

/**
 * Class Result
 *
 * @package AmeliaBooking\Infrastructure\DB\MySQLi
 */
class Result
{
    private $value;

    /**
     * @param string $value
     *
     * @return mixed
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
