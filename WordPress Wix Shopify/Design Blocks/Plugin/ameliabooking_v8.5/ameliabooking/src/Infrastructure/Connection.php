<?php

/**
 * @author Slavko Babic
 * @date   2017-08-21
 */

namespace AmeliaBooking\Infrastructure;

use mysqli;
use PDO;

/**
 * Class Connection
 *
 * @package Infrastructure
 */
abstract class Connection
{
    /** @var string $username */
    protected $username;

    /** @var string $password */
    protected $password;

    /** @var string $charset */
    protected $charset;

    /** @var PDO|mysqli $handler */
    protected $handler;

    /** @var int port */
    protected $port;

    /** @var string $host */
    protected $host;

    /** @var string $name */
    protected $database;

    /** @var string $socket */
    protected $socket;

    /** @var string $socketPath */
    protected $socketPath;

    /**
     * Connection constructor.
     *
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int    $port
     * @param string $charset
     */
    public function __construct(
        $host,
        $database,
        $username,
        $password,
        $charset = 'utf8',
        $port = 3306
    ) {
        $this->database = (string)$database;
        $this->username = (string)$username;
        $this->password = (string)$password;
        $this->host     = $this->socket = (string)$host;
        $this->port     = (int)$port;
        $this->charset  = (string)$charset;
    }

    /**
     * @return PDO|mysqli
     */
    public function __invoke()
    {
        return $this->handler;
    }

    /**
     *
     */
    protected function socketHandler()
    {
        if (strpos($this->socket, ':') === false) {
            $this->host = $this->socket;

            return;
        }

        $data = explode(':', $this->socket);

        $this->host = $data[0];

        if (isset($data[1]) && is_numeric($data[1]) && (int)$data[1]) {
            $this->port = $data[1];
        } elseif (isset($data[1]) && strpos($data[1], '/') !== false) {
            $position = strpos($data[1], '/');

            if ($position === 0) {
                $this->socketPath = $data[1];
            } else {
                $this->port       = substr($data[1], 0, $position);
                $this->socketPath = substr($data[1], $position);
            }
        } elseif (isset($data[1]) && (int)$data[1]) {
            $this->port = $data[1];
        }
    }
}
