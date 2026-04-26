<?php

/**
 * @author Slavko Babic
 * @date   2017-08-21
 */

namespace AmeliaBooking\Infrastructure\DB\PDO;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use PDO;

/**
 * Class Connection
 *
 * @package AmeliaBooking\Infrastructure\DB\PDO
 */
class Connection extends \AmeliaBooking\Infrastructure\Connection
{
    /** @var PDO $pdo */
    protected $pdo;

    /** @var string $dns */
    private $dns;

    /** @var string $driver */
    private $driver = 'mysql';

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

        $this->handler = new PDO(
            $this->dns(),
            $this->username,
            $this->password,
            $this->getOptions()
        );

        $settingsService = new SettingsService(new SettingsStorage());

        $emulatePrepares = $settingsService->getSetting('db', 'pdoEmulatePrepares');

        $this->handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->handler->setAttribute(
            PDO::ATTR_EMULATE_PREPARES,
            $emulatePrepares !== null ? $emulatePrepares : false
        );

        $this->handler->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->handler->exec('SET SESSION sql_mode = "TRADITIONAL"');

        $this->handler->exec('SET FOREIGN_KEY_CHECKS = 0');

        if ($settingsService->getSetting('db', 'pdoBigSelect')) {
            $this->handler->exec('SET SQL_BIG_SELECTS = 1');
        }
    }

    /**
     * @return string
     */
    private function dns()
    {
        if ($this->dns) {
            return $this->dns;
        }

        $this->socketHandler();

        $socketPath = property_exists($this, 'socketPath') && $this->socketPath ? ";unix_socket=$this->socketPath" : '';

        return $this->dns = "{$this->driver}:host={$this->host};port={$this->port}';dbname={$this->database}{$socketPath}";
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        $options = [
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION,
        ];

        if (defined('DB_CHARSET')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'set names ' . DB_CHARSET;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        $ssl = apply_filters('amelia_change_ssl_settings', $settingsService->getSetting('db', 'ssl'));

        if ($ssl['enable']) {
            if ($ssl['enable']) {
                if ($ssl['key'] !== null) {
                    $options[PDO::MYSQL_ATTR_SSL_KEY] = $ssl['key'];
                }

                if ($ssl['cert'] !== null) {
                    $options[PDO::MYSQL_ATTR_SSL_CERT] = $ssl['cert'];
                }

                if ($ssl['ca'] !== null) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl['ca'];
                }

                if ($ssl['verify_cert'] !== null) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $ssl['verify_cert'];
                }
            }
        }

        return $options;
    }
}
