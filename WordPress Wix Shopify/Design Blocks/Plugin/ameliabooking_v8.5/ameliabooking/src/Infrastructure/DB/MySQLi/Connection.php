<?php

namespace AmeliaBooking\Infrastructure\DB\MySQLi;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use mysqli;

/**
 * Class Connection
 *
 * @package AmeliaBooking\Infrastructure\DB\MySQLi
 */
class Connection extends \AmeliaBooking\Infrastructure\Connection
{
    /** @var Statement $statement */
    public $statement;

    /** @var Result $result */
    private $result;

    /** @var Query $query */
    private $query;

    /** @var mysqli $mysqli */
    private $mysqli;

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
        parent::__construct(
            $host,
            $database,
            $username,
            $password,
            $charset,
            $port
        );

        $this->socketHandler();

        $this->result = new Result();

        $this->query = new Query();

        if (property_exists($this, 'socketPath') && $this->socketPath) {
            $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port, $this->socketPath);
        } else {
            $this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->database, $this->port);
        }

        $settingsService = new SettingsService(new SettingsStorage());

        $ssl = apply_filters('amelia_change_ssl_settings', $settingsService->getSetting('db', 'ssl'));

        if ($ssl['enable']) {
            $this->mysqli->ssl_set($ssl['key'], $ssl['cert'], $ssl['ca'], null, null);
        }

        $this->mysqli->set_charset($this->charset);

        $stmt = $this->mysqli->prepare('SET SESSION sql_mode = "TRADITIONAL"');
        $stmt->execute();

        $stmt = $this->mysqli->prepare('SET FOREIGN_KEY_CHECKS = 0');
        $stmt->execute();

        $settingsService = new SettingsService(new SettingsStorage());

        if ($settingsService->getSetting('db', 'pdoBigSelect')) {
            $stmt = $this->mysqli->prepare('SET SQL_BIG_SELECTS = 1');
            $stmt->execute();
        }

        $this->statement = new Statement($this->mysqli, $this->result, $this->query);

        $this->handler = $this;
    }

    /**
     * @param string $query
     *
     * @return mixed
     */
    public function query($query)
    {
        $this->result->setValue($this->mysqli->query($query));

        return $this->statement;
    }

    /**
     * @param string $query
     *
     * @return mixed
     */
    public function prepare($query)
    {
        $this->query->setValue($query);

        return $this->statement;
    }

    /**
     *
     * @return string|false
     */
    public function lastInsertId()
    {
        return $this->mysqli->insert_id;
    }

    /**
     *
     * @return mixed
     */
    public function beginTransaction()
    {
        return $this->mysqli->begin_transaction();
    }

    /**
     * @return bool
     */
    public function commit()
    {
        return $this->mysqli->commit();
    }

    /**
     * @return bool
     */
    public function rollback()
    {
        return $this->mysqli->rollback();
    }
}
