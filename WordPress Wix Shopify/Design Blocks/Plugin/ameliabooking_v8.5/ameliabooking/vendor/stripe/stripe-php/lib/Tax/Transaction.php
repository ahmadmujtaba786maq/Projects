<?php

// File generated from our OpenAPI spec

namespace AmeliaStripe\Tax;

/**
 * A Tax Transaction records the tax collected from or refunded to your customer.
 *
 * Related guide: <a href="https://stripe.com/docs/tax/custom#tax-transaction">Calculate tax in your custom payment flow</a>
 *
 * @property string $id Unique identifier for the transaction.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $customer The ID of an existing <a href="https://stripe.com/docs/api/customers/object">Customer</a> used for the resource.
 * @property (object{address: null|(object{city: null|string, country: string, line1: null|string, line2: null|string, postal_code: null|string, state: null|string}&\AmeliaStripe\StripeObject), address_source: null|string, ip_address: null|string, tax_ids: (object{type: string, value: string}&\AmeliaStripe\StripeObject)[], taxability_override: string}&\AmeliaStripe\StripeObject) $customer_details
 * @property null|\AmeliaStripe\Collection<TransactionLineItem> $line_items The tax collected or refunded, by line item.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\AmeliaStripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property int $posted_at The Unix timestamp representing when the tax liability is assumed or reduced.
 * @property string $reference A custom unique identifier, such as 'myOrder_123'.
 * @property null|(object{original_transaction: null|string}&\AmeliaStripe\StripeObject) $reversal If <code>type=reversal</code>, contains information about what was reversed.
 * @property null|(object{address: (object{city: null|string, country: string, line1: null|string, line2: null|string, postal_code: null|string, state: null|string}&\AmeliaStripe\StripeObject)}&\AmeliaStripe\StripeObject) $ship_from_details The details of the ship from location, such as the address.
 * @property null|(object{amount: int, amount_tax: int, shipping_rate?: string, tax_behavior: string, tax_breakdown?: ((object{amount: int, jurisdiction: (object{country: string, display_name: string, level: string, state: null|string}&\AmeliaStripe\StripeObject), sourcing: string, tax_rate_details: null|(object{display_name: string, percentage_decimal: string, tax_type: string}&\AmeliaStripe\StripeObject), taxability_reason: string, taxable_amount: int}&\AmeliaStripe\StripeObject))[], tax_code: string}&\AmeliaStripe\StripeObject) $shipping_cost The shipping cost details for the transaction.
 * @property int $tax_date Timestamp of date at which the tax rules and rates in effect applies for the calculation.
 * @property string $type If <code>reversal</code>, this transaction reverses an earlier transaction.
 */
class Transaction extends \AmeliaStripe\ApiResource
{
    const OBJECT_NAME = 'tax.transaction';

    const TYPE_REVERSAL = 'reversal';
    const TYPE_TRANSACTION = 'transaction';

    /**
     * Retrieves a Tax <code>Transaction</code> object.
     *
     * @param array|string $id the ID of the API resource to retrieve, or an options array containing an `id` key
     * @param null|array|string $opts
     *
     * @return Transaction
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
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return Transaction the created transaction
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function createFromCalculation($params = null, $opts = null)
    {
        $url = static::classUrl() . '/create_from_calculation';
        list($response, $opts) = static::_staticRequest('post', $url, $params, $opts);
        $obj = \AmeliaStripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return Transaction the created transaction
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function createReversal($params = null, $opts = null)
    {
        $url = static::classUrl() . '/create_reversal';
        list($response, $opts) = static::_staticRequest('post', $url, $params, $opts);
        $obj = \AmeliaStripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * @param string $id
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return \AmeliaStripe\Collection<TransactionLineItem> list of transaction line items
     *
     * @throws \AmeliaStripe\Exception\ApiErrorException if the request fails
     */
    public static function allLineItems($id, $params = null, $opts = null)
    {
        $url = static::resourceUrl($id) . '/line_items';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \AmeliaStripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }
}
