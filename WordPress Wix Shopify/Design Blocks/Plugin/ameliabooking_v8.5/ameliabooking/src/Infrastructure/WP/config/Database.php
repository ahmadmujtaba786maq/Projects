<?php

namespace AmeliaBooking\Infrastructure\WP\config;

use InvalidArgumentException;

/**
 * Class Database
 *
 * @package AmeliaBooking\Infrastructure\WP\config
 */
class Database
{
    private $database;
    private $username;
    private $password;
    private $host;
    private $charset;
    private $collate;
    public static $connection;

    /**
     * Database constructor.
     */
    public function __construct()
    {
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;
        $this->host     = defined('DB_HOST') && DB_HOST ? DB_HOST : 'localhost';
        $this->charset  = defined('DB_CHARSET') ? DB_CHARSET : '';
        $this->collate  = defined('DB_COLLATE') && DB_COLLATE ? DB_COLLATE : '';
    }

    /**\
     * @param $property
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __invoke($property)
    {
        if (!isset($this->$property)) {
            throw new InvalidArgumentException(
                "Property \"{$property}\" does not exists. "
            );
        }

        return $this->$property;
    }
}
