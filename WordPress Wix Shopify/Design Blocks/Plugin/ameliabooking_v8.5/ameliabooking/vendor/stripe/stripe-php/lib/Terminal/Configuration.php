<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Terminal;

/**
 * A Configurations object represents how features should be configured for terminal readers.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|(object{splashscreen?: string|\AmeliaStripe\File}&\AmeliaStripe\StripeObject) $bbpos_wisepos_e
 * @property null|bool $is_account_default Whether this Configuration is the default for your account
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string $name String indicating the name of the Configuration object, set by the user
 * @property null|(object{enabled: null|bool}&\AmeliaStripe\StripeObject) $offline
 * @property null|(object{end_hour: int, start_hour: int}&\AmeliaStripe\StripeObject) $reboot_window
 * @property null|(object{splashscreen?: string|\AmeliaStripe\File}&\AmeliaStripe\StripeObject) $stripe_s700
 * @property null|(object{aud?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), cad?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), chf?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), czk?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), dkk?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), eur?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), gbp?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), hkd?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), jpy?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), myr?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), nok?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), nzd?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), pln?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), sek?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), sgd?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject), usd?: (object{fixed_amounts?: null|int[], percentages?: null|int[], smart_tip_threshold?: int}&\AmeliaStripe\StripeObject)}&\AmeliaStripe\StripeObject) $tipping
 * @property null|(object{splashscreen?: string|\AmeliaStripe\File}&\AmeliaStripe\StripeObject) $verifone_p400
 * @property null|(object{enterprise_eap_peap?: (object{ca_certificate_file?: string, password: string, ssid: string, username: string}&\AmeliaStripe\StripeObject), enterprise_eap_tls?: (object{ca_certificate_file?: string, client_certificate_file: string, private_key_file: string, private_key_file_password?: string, ssid: string}&\AmeliaStripe\StripeObject), personal_psk?: (object{password: string, ssid: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject) $wifi
 */
class Configuration extends \AmeliaStripe\ApiResource
{
    const OBJECT_NAME = 'terminal.configuration';

    use \AmeliaStripe\ApiOperations\Update;

    /**
     * Creates a new <code>Configuration</code> object.
     *
     * @param null|array{bbpos_wisepos_e?: array{splashscreen?: null|string}, expand?: string[], name?: string, offline?: null|array{enabled: bool}, reboot_window?: array{end_hour: int, start_hour: int}, stripe_s700?: array{splashscreen?: null|string}, tipping?: null|array{aud?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, cad?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, chf?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, czk?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, dkk?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, eur?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, gbp?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, hkd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, jpy?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, myr?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, nok?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, nzd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, pln?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, sek?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, sgd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, usd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}}, verifone_p400?: array{splashscreen?: null|string}, wifi?: null|array{enterprise_eap_peap?: array{ca_certificate_file?: string, password: string, ssid: string, username: string}, enterprise_eap_tls?: array{ca_certificate_file?: string, client_certificate_file: string, private_key_file: string, private_key_file_password?: string, ssid: string}, personal_psk?: array{password: string, ssid: string}, type: string}} $params
     * @param null|array|string $options
     *
     * @return Configuration the created resource
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function create($params = null, $options = null)
    {
        self::_validateParams($params);
        $url = static::classUrl();

        list($response, $opts) = static::_staticRequest('post', $url, $params, $options);
        $obj = \AmeliaStripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * Deletes a <code>Configuration</code> object.
     *
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return Configuration the deleted resource
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public function delete($params = null, $opts = null)
    {
        self::_validateParams($params);

        $url = $this->instanceUrl();
        list($response, $opts) = $this->_request('delete', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }

    /**
     * Returns a list of <code>Configuration</code> objects.
     *
     * @param null|array{ending_before?: string, expand?: string[], is_account_default?: bool, limit?: int, starting_after?: string} $params
     * @param null|array|string $opts
     *
     * @return \AmeliaStripe\Collection<Configuration> of ApiResources
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function all($params = null, $opts = null)
    {
        $url = static::classUrl();

        return static::_requestPage($url, \AmeliaStripe\Collection::class, $params, $opts);
    }

    /**
     * Retrieves a <code>Configuration</code> object.
     *
     * @param array|string $id the ID of the API resource to retrieve, or an options array containing an `id` key
     * @param null|array|string $opts
     *
     * @return Configuration
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function retrieve($id, $opts = null)
    {
        $opts = \AmeliaStripe\Util\RequestOptions::parse($opts);
        $instance = new static($id, $opts);
        $instance->refresh();

        return $instance;
    }

    /**
     * Updates a new <code>Configuration</code> object.
     *
     * @param string $id the ID of the resource to update
     * @param null|array{bbpos_wisepos_e?: null|array{splashscreen?: null|string}, expand?: string[], name?: string, offline?: null|array{enabled: bool}, reboot_window?: null|array{end_hour: int, start_hour: int}, stripe_s700?: null|array{splashscreen?: null|string}, tipping?: null|array{aud?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, cad?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, chf?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, czk?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, dkk?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, eur?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, gbp?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, hkd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, jpy?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, myr?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, nok?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, nzd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, pln?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, sek?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, sgd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}, usd?: array{fixed_amounts?: int[], percentages?: int[], smart_tip_threshold?: int}}, verifone_p400?: null|array{splashscreen?: null|string}, wifi?: null|array{enterprise_eap_peap?: array{ca_certificate_file?: string, password: string, ssid: string, username: string}, enterprise_eap_tls?: array{ca_certificate_file?: string, client_certificate_file: string, private_key_file: string, private_key_file_password?: string, ssid: string}, personal_psk?: array{password: string, ssid: string}, type: string}} $params
     * @param null|array|string $opts
     *
     * @return Configuration the updated resource
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function update($id, $params = null, $opts = null)
    {
        self::_validateParams($params);
        $url = static::resourceUrl($id);

        list($response, $opts) = static::_staticRequest('post', $url, $params, $opts);
        $obj = \AmeliaStripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }
}
