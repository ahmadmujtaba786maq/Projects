<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Tax;

/**
 * A Tax <code>Registration</code> lets us know that your business is registered to collect tax on payments within a region, enabling you to <a href="https://stripe.com/docs/tax">automatically collect tax</a>.
 *
 * Stripe doesn't register on your behalf with the relevant authorities when you create a Tax <code>Registration</code> object. For more information on how to register to collect tax, see <a href="https://stripe.com/docs/tax/registering">our guide</a>.
 *
 * Related guide: <a href="https://stripe.com/docs/tax/registrations-api">Using the Registrations API</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $active_from Time at which the registration becomes active. Measured in seconds since the Unix epoch.
 * @property string $country Two-letter country code (<a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">ISO 3166-1 alpha-2</a>).
 * @property (object{ae?: (object{type: string}&\AmeliaStripe\StripeObject), al?: (object{type: string}&\AmeliaStripe\StripeObject), am?: (object{type: string}&\AmeliaStripe\StripeObject), ao?: (object{type: string}&\AmeliaStripe\StripeObject), at?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), au?: (object{type: string}&\AmeliaStripe\StripeObject), aw?: (object{type: string}&\AmeliaStripe\StripeObject), az?: (object{type: string}&\AmeliaStripe\StripeObject), ba?: (object{type: string}&\AmeliaStripe\StripeObject), bb?: (object{type: string}&\AmeliaStripe\StripeObject), bd?: (object{type: string}&\AmeliaStripe\StripeObject), be?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), bf?: (object{type: string}&\AmeliaStripe\StripeObject), bg?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), bh?: (object{type: string}&\AmeliaStripe\StripeObject), bj?: (object{type: string}&\AmeliaStripe\StripeObject), bs?: (object{type: string}&\AmeliaStripe\StripeObject), by?: (object{type: string}&\AmeliaStripe\StripeObject), ca?: (object{province_standard?: (object{province: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), cd?: (object{type: string}&\AmeliaStripe\StripeObject), ch?: (object{type: string}&\AmeliaStripe\StripeObject), cl?: (object{type: string}&\AmeliaStripe\StripeObject), cm?: (object{type: string}&\AmeliaStripe\StripeObject), co?: (object{type: string}&\AmeliaStripe\StripeObject), cr?: (object{type: string}&\AmeliaStripe\StripeObject), cv?: (object{type: string}&\AmeliaStripe\StripeObject), cy?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), cz?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), de?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), dk?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), ec?: (object{type: string}&\AmeliaStripe\StripeObject), ee?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), eg?: (object{type: string}&\AmeliaStripe\StripeObject), es?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), et?: (object{type: string}&\AmeliaStripe\StripeObject), fi?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), fr?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), gb?: (object{type: string}&\AmeliaStripe\StripeObject), ge?: (object{type: string}&\AmeliaStripe\StripeObject), gn?: (object{type: string}&\AmeliaStripe\StripeObject), gr?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), hr?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), hu?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), id?: (object{type: string}&\AmeliaStripe\StripeObject), ie?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), in?: (object{type: string}&\AmeliaStripe\StripeObject), is?: (object{type: string}&\AmeliaStripe\StripeObject), it?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), jp?: (object{type: string}&\AmeliaStripe\StripeObject), ke?: (object{type: string}&\AmeliaStripe\StripeObject), kg?: (object{type: string}&\AmeliaStripe\StripeObject), kh?: (object{type: string}&\AmeliaStripe\StripeObject), kr?: (object{type: string}&\AmeliaStripe\StripeObject), kz?: (object{type: string}&\AmeliaStripe\StripeObject), la?: (object{type: string}&\AmeliaStripe\StripeObject), lt?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), lu?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), lv?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), ma?: (object{type: string}&\AmeliaStripe\StripeObject), md?: (object{type: string}&\AmeliaStripe\StripeObject), me?: (object{type: string}&\AmeliaStripe\StripeObject), mk?: (object{type: string}&\AmeliaStripe\StripeObject), mr?: (object{type: string}&\AmeliaStripe\StripeObject), mt?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), mx?: (object{type: string}&\AmeliaStripe\StripeObject), my?: (object{type: string}&\AmeliaStripe\StripeObject), ng?: (object{type: string}&\AmeliaStripe\StripeObject), nl?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), no?: (object{type: string}&\AmeliaStripe\StripeObject), np?: (object{type: string}&\AmeliaStripe\StripeObject), nz?: (object{type: string}&\AmeliaStripe\StripeObject), om?: (object{type: string}&\AmeliaStripe\StripeObject), pe?: (object{type: string}&\AmeliaStripe\StripeObject), ph?: (object{type: string}&\AmeliaStripe\StripeObject), pl?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), pt?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), ro?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), rs?: (object{type: string}&\AmeliaStripe\StripeObject), ru?: (object{type: string}&\AmeliaStripe\StripeObject), sa?: (object{type: string}&\AmeliaStripe\StripeObject), se?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), sg?: (object{type: string}&\AmeliaStripe\StripeObject), si?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), sk?: (object{standard?: (object{place_of_supply_scheme: string}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), sn?: (object{type: string}&\AmeliaStripe\StripeObject), sr?: (object{type: string}&\AmeliaStripe\StripeObject), th?: (object{type: string}&\AmeliaStripe\StripeObject), tj?: (object{type: string}&\AmeliaStripe\StripeObject), tr?: (object{type: string}&\AmeliaStripe\StripeObject), tz?: (object{type: string}&\AmeliaStripe\StripeObject), ug?: (object{type: string}&\AmeliaStripe\StripeObject), us?: (object{local_amusement_tax?: (object{jurisdiction: string}&\AmeliaStripe\StripeObject), local_lease_tax?: (object{jurisdiction: string}&\AmeliaStripe\StripeObject), state: string, state_sales_tax?: (object{elections?: (object{jurisdiction?: string, type: string}&\AmeliaStripe\StripeObject)[]}&\AmeliaStripe\StripeObject), type: string}&\AmeliaStripe\StripeObject), uy?: (object{type: string}&\AmeliaStripe\StripeObject), uz?: (object{type: string}&\AmeliaStripe\StripeObject), vn?: (object{type: string}&\AmeliaStripe\StripeObject), za?: (object{type: string}&\AmeliaStripe\StripeObject), zm?: (object{type: string}&\AmeliaStripe\StripeObject), zw?: (object{type: string}&\AmeliaStripe\StripeObject)}&\AmeliaStripe\StripeObject) $country_options
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|int $expires_at If set, the registration stops being active at this time. If not set, the registration will be active indefinitely. Measured in seconds since the Unix epoch.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property string $status The status of the registration. This field is present for convenience and can be deduced from <code>active_from</code> and <code>expires_at</code>.
 */
class Registration extends \AmeliaStripe\ApiResource
{
    const OBJECT_NAME = 'tax.registration';

    use \AmeliaStripe\ApiOperations\Update;

    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_SCHEDULED = 'scheduled';

    /**
     * Creates a new Tax <code>Registration</code> object.
     *
     * @param null|array{active_from: array|int|string, country: string, country_options: array{ae?: array{type: string}, al?: array{type: string}, am?: array{type: string}, ao?: array{type: string}, at?: array{standard?: array{place_of_supply_scheme: string}, type: string}, au?: array{type: string}, aw?: array{type: string}, az?: array{type: string}, ba?: array{type: string}, bb?: array{type: string}, bd?: array{type: string}, be?: array{standard?: array{place_of_supply_scheme: string}, type: string}, bf?: array{type: string}, bg?: array{standard?: array{place_of_supply_scheme: string}, type: string}, bh?: array{type: string}, bj?: array{type: string}, bs?: array{type: string}, by?: array{type: string}, ca?: array{province_standard?: array{province: string}, type: string}, cd?: array{type: string}, ch?: array{type: string}, cl?: array{type: string}, cm?: array{type: string}, co?: array{type: string}, cr?: array{type: string}, cv?: array{type: string}, cy?: array{standard?: array{place_of_supply_scheme: string}, type: string}, cz?: array{standard?: array{place_of_supply_scheme: string}, type: string}, de?: array{standard?: array{place_of_supply_scheme: string}, type: string}, dk?: array{standard?: array{place_of_supply_scheme: string}, type: string}, ec?: array{type: string}, ee?: array{standard?: array{place_of_supply_scheme: string}, type: string}, eg?: array{type: string}, es?: array{standard?: array{place_of_supply_scheme: string}, type: string}, et?: array{type: string}, fi?: array{standard?: array{place_of_supply_scheme: string}, type: string}, fr?: array{standard?: array{place_of_supply_scheme: string}, type: string}, gb?: array{type: string}, ge?: array{type: string}, gn?: array{type: string}, gr?: array{standard?: array{place_of_supply_scheme: string}, type: string}, hr?: array{standard?: array{place_of_supply_scheme: string}, type: string}, hu?: array{standard?: array{place_of_supply_scheme: string}, type: string}, id?: array{type: string}, ie?: array{standard?: array{place_of_supply_scheme: string}, type: string}, in?: array{type: string}, is?: array{type: string}, it?: array{standard?: array{place_of_supply_scheme: string}, type: string}, jp?: array{type: string}, ke?: array{type: string}, kg?: array{type: string}, kh?: array{type: string}, kr?: array{type: string}, kz?: array{type: string}, la?: array{type: string}, lt?: array{standard?: array{place_of_supply_scheme: string}, type: string}, lu?: array{standard?: array{place_of_supply_scheme: string}, type: string}, lv?: array{standard?: array{place_of_supply_scheme: string}, type: string}, ma?: array{type: string}, md?: array{type: string}, me?: array{type: string}, mk?: array{type: string}, mr?: array{type: string}, mt?: array{standard?: array{place_of_supply_scheme: string}, type: string}, mx?: array{type: string}, my?: array{type: string}, ng?: array{type: string}, nl?: array{standard?: array{place_of_supply_scheme: string}, type: string}, no?: array{type: string}, np?: array{type: string}, nz?: array{type: string}, om?: array{type: string}, pe?: array{type: string}, ph?: array{type: string}, pl?: array{standard?: array{place_of_supply_scheme: string}, type: string}, pt?: array{standard?: array{place_of_supply_scheme: string}, type: string}, ro?: array{standard?: array{place_of_supply_scheme: string}, type: string}, rs?: array{type: string}, ru?: array{type: string}, sa?: array{type: string}, se?: array{standard?: array{place_of_supply_scheme: string}, type: string}, sg?: array{type: string}, si?: array{standard?: array{place_of_supply_scheme: string}, type: string}, sk?: array{standard?: array{place_of_supply_scheme: string}, type: string}, sn?: array{type: string}, sr?: array{type: string}, th?: array{type: string}, tj?: array{type: string}, tr?: array{type: string}, tz?: array{type: string}, ug?: array{type: string}, us?: array{local_amusement_tax?: array{jurisdiction: string}, local_lease_tax?: array{jurisdiction: string}, state: string, state_sales_tax?: array{elections: array{jurisdiction?: string, type: string}[]}, type: string}, uy?: array{type: string}, uz?: array{type: string}, vn?: array{type: string}, za?: array{type: string}, zm?: array{type: string}, zw?: array{type: string}}, expand?: string[], expires_at?: int} $params
     * @param null|array|string $options
     *
     * @return Registration the created resource
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
     * Returns a list of Tax <code>Registration</code> objects.
     *
     * @param null|array{ending_before?: string, expand?: string[], limit?: int, starting_after?: string, status?: string} $params
     * @param null|array|string $opts
     *
     * @return \AmeliaStripe\Collection<Registration> of ApiResources
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function all($params = null, $opts = null)
    {
        $url = static::classUrl();

        return static::_requestPage($url, \AmeliaStripe\Collection::class, $params, $opts);
    }

    /**
     * Returns a Tax <code>Registration</code> object.
     *
     * @param array|string $id the ID of the API resource to retrieve, or an options array containing an `id` key
     * @param null|array|string $opts
     *
     * @return Registration
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
     * Updates an existing Tax <code>Registration</code> object.
     *
     * A registration cannot be deleted after it has been created. If you wish to end a
     * registration you may do so by setting <code>expires_at</code>.
     *
     * @param string $id the ID of the resource to update
     * @param null|array{active_from?: array|int|string, expand?: string[], expires_at?: null|array|int|string} $params
     * @param null|array|string $opts
     *
     * @return Registration the updated resource
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
