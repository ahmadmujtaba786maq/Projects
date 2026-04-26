<?php

/** @noinspection PhpUnusedParameterInspection */

/**
 * Database hook for activation
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions;

use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\WP\HelperService\HelperService;
use AmeliaBooking\Infrastructure\WP\SettingsService\SettingsStorage;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use WP_Error;
use WP_Upgrader;

/**
 * Class AutoUpdateHook
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions
 */
class AutoUpdateHook
{
    /**
     * Add our self-hosted auto update plugin to the filter transient
     *
     * @param $transient
     *
     * @return object $ transient
     */
    public static function checkUpdate($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        /** @var string $purchaseCode */
        $purchaseCode = $settingsService->getSetting('activation', 'purchaseCodeStore');

        /** @var string $envatoTokenEmail */
        $envatoTokenEmail = $settingsService->getSetting('activation', 'envatoTokenEmail');

        // Get the remote info
        $remoteInformation = self::getRemoteInformation($purchaseCode, $envatoTokenEmail);

        // If a newer version is available, add the update
        if ($remoteInformation && version_compare(AMELIA_VERSION, $remoteInformation->new_version, '<')) {
            $transient->response[AMELIA_PLUGIN_SLUG] = $remoteInformation;
        }

        return $transient;
    }

    /**
     * Add our self-hosted description to the filter
     *
     * @param bool  $response
     * @param array $action
     * @param       $args
     *
     * @return bool|object
     */
    public static function checkInfo($response, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return $response;
        }

        if (empty($args->slug)) {
            return $response;
        }

        $settingsService = new SettingsService(new SettingsStorage());

        /** @var string $purchaseCode */
        $purchaseCode = $settingsService->getSetting('activation', 'purchaseCodeStore');

        /** @var string $envatoTokenEmail */
        $envatoTokenEmail = $settingsService->getSetting('activation', 'envatoTokenEmail');

        if ($args->slug === AMELIA_PLUGIN_SLUG) {
            return self::getRemoteInformation($purchaseCode, $envatoTokenEmail);
        }

        return $response;
    }

    /**
     * Add a message for unavailable auto update on plugins page if plugin is not activated
     */
    public static function addMessageOnPluginsPage()
    {
        /** @var SettingsService $settingsService */
        $settingsService = new SettingsService(new SettingsStorage());

        /** @var bool $activated */
        $activated = $settingsService->getSetting('activation', 'active');

        /** @var array $settingsStrings */
        $settingsStrings = BackendStrings::getSettingsStrings();

        /** @var string $url */
        $url = AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-settings&activeSetting=activation';

        $redirect = '<a href="' . $url . '" target="_blank">' . $settingsStrings['settings_lower'] . '</a>';

        if (!$activated) {
            echo sprintf(' ' . $settingsStrings['plugin_not_activated'], $redirect);
        }
    }

    /**
     * Add error message on plugin update if plugin is not activated
     *
     * @param bool        $reply
     * @param string      $package
     * @param WP_Upgrader $updater
     *
     * @return WP_Error|string|bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function addMessageOnUpdate($reply, $package, $updater)
    {
        /** @var array $settingsStrings */
        $settingsStrings = BackendStrings::getSettingsStrings();

        $url = AMELIA_SITE_URL . '/wp-admin/admin.php?page=wpamelia-settings&activeSetting=activation';

        $redirect = '<a href="' . $url . '" target="_blank">' . $settingsStrings['settings_lower'] . '</a>';

        if (!$package) {
            return new WP_Error(
                'amelia_not_activated',
                sprintf(' ' . $settingsStrings['plugin_not_activated'], $redirect)
            );
        }

        return $reply;
    }

    /**
     * Get information about the remote version
     *
     * @param string $purchaseCode
     * @param string $envatoTokenEmail
     *
     * @return bool|object
     */
    private static function getRemoteInformation($purchaseCode, $envatoTokenEmail)
    {
        $serverName = (defined('WP_CLI') && WP_CLI) ? php_uname('n') : $_SERVER['SERVER_NAME'];

        $request = wp_remote_post(
            AMELIA_STORE_API_URL . 'autoupdate/info',
            [
                'body' => [
                    'slug'             => 'ameliabooking',
                    'purchaseCode'     => trim($purchaseCode),
                    'envatoTokenEmail' => trim($envatoTokenEmail),
                    'domain'           => self::getDomain(
                        $serverName
                    ),
                    'subdomain'        => self::getSubDomain(
                        $serverName
                    )
                ]
            ]
        );

        if ((!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) && isset($request['body'])) {
            $body = json_decode($request['body']);

            return $body && isset($body->info) ? unserialize($body->info) : false;
        }

        return false;
    }

    /**
     * @param $domain
     *
     * @return mixed
     */
    public static function extractDomain($domain)
    {
        $topLevelDomainsJSON = require(AMELIA_PATH . '/view/backend/top-level-domains.php');
        $topLevelDomains     =  json_decode($topLevelDomainsJSON, true);
        $tempDomain          = '';

        $extractDomainArray = explode('.', $domain);
        for ($i = 0; $i <= count($extractDomainArray); $i++) {
            $slicedDomainArray  = array_slice($extractDomainArray, $i);
            $slicedDomainString = implode('.', $slicedDomainArray);

            if (in_array($slicedDomainString, $topLevelDomains)) {
                $tempDomain = array_slice($extractDomainArray, $i - 1);
                break;
            }
        }
        if ($tempDomain == '') {
            $tempDomain = $extractDomainArray;
        }

        return implode('.', $tempDomain);
    }

    /**
     * @param $domain
     *
     * @return string
     */
    public static function extractSubdomain($domain)
    {
        $host   = explode('.', $domain);
        $domain = self::extractDomain($domain);
        $domain = explode('.', $domain);
        return implode('.', array_diff($host, $domain));
    }

    /**
     * Check if serve name is IPv4 or Ipv6
     *
     * @param $domain
     *
     * @return boolean
     * phpcs:disable Generic.Files.LineLength
     */
    public static function isIP($domain)
    {

        if (
            preg_match("/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", $domain) ||
            preg_match("/^((?:[0-9A-Fa-f]{1,4}))((?::[0-9A-Fa-f]{1,4}))*::((?:[0-9A-Fa-f]{1,4}))((?::[0-9A-Fa-f]{1,4}))*|((?:[0-9A-Fa-f]{1,4}))((?::[0-9A-Fa-f]{1,4})){7}$/", $domain)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove www from server name
     *
     * @param $url
     *
     * @return string
     */
    public static function removeWWW($url)
    {
        if (in_array(substr($url, 0, 5), ['www1.','www2.','www3.','www4.'])) {
            return substr_replace($url, "", 0, 5);
        } elseif (substr($url, 0, 4) === 'www.') {
            return substr_replace($url, "", 0, 4);
        }
        return $url;
    }

    /**
     * Get filtered domain
     *
     * @param $domain
     *
     * @return string
     */
    public static function getDomain($domain)
    {
        $domain = self::isIP($domain) ? $domain : self::extractDomain(self::removeWWW($domain));
        return $domain;
    }
    /**
     * Get filtered subdomain
     *
     * @param $subdomain
     *
     * @return string
     */
    public static function getSubDomain($subdomain)
    {
        $subdomain = self::isIP($subdomain) ? '' : self::extractSubdomain(self::removeWWW($subdomain));
        return $subdomain;
    }
}
